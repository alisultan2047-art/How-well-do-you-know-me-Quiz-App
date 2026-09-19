import express from "express";
import { spawn, ChildProcess } from "child_process";
import path from "path";
import http from "http";
import fs from "fs";

const app = express();
const PORT = 3000;
const PHP_PORT = 8080;

function findPhpBinary(): string {
  const candidates = [
    "/usr/local/bin/php",
    "/usr/bin/php8.2",
    "/usr/bin/php",
    "php8.2",
    "php",
  ];
  for (const p of candidates) {
    if (p.startsWith("/") && fs.existsSync(p)) {
      return p;
    }
  }
  return "php";
}

let phpProcess: ChildProcess | null = null;

function startPhp() {
  const binary = findPhpBinary();
  console.log(`Starting PHP built-in server using '${binary}' on 127.0.0.1:${PHP_PORT}...`);

  const phpArgs = [
    "-d", "extension=pdo.so",
    "-d", "extension=pdo_sqlite.so",
    "-S", `127.0.0.1:${PHP_PORT}`,
    "-t", process.cwd(),
  ];

  try {
    phpProcess = spawn(binary, phpArgs, {
      stdio: ["ignore", "pipe", "pipe"],
    });

    phpProcess.stdout?.on("data", (data) => {
      console.log(`[PHP]: ${data}`);
    });

    phpProcess.stderr?.on("data", (data) => {
      const msg = data.toString();
      // PHP's built-in web server routes routine HTTP access logs (200, 304, Accepted, Closing) to stderr
      const isAccessLog = /Accepted|Closing|\[(2\d\d|3\d\d)\]:/i.test(msg);
      if (isAccessLog) {
        return; // suppress routine request/response logging from stderr
      }
      if (/fatal|parse error|uncaught exception/i.test(msg)) {
        console.error(`[PHP Exception]: ${msg.trim()}`);
      } else {
        console.log(`[PHP Notice]: ${msg.trim()}`);
      }
    });

    phpProcess.on("error", (err) => {
      console.error("[PHP Process Error]:", err);
    });

    phpProcess.on("exit", (code, signal) => {
      console.warn(`[PHP Process exited] code=${code} signal=${signal}`);
    });
  } catch (err) {
    console.error("Failed to spawn PHP process:", err);
  }
}

startPhp();

process.on("exit", () => phpProcess?.kill());
process.on("SIGINT", () => {
  phpProcess?.kill();
  process.exit();
});
process.on("SIGTERM", () => {
  phpProcess?.kill();
  process.exit();
});

// Download ready-to-upload InfinityFree zip endpoint
app.get("/api/download-infinityfree-zip", (req, res) => {
  const zipPath = path.join(process.cwd(), "InfinityFree_Ali_Quiz_Deploy.zip");
  if (fs.existsSync(zipPath)) {
    res.download(zipPath, "InfinityFree_Ali_Quiz_Deploy.zip");
  } else {
    res.status(404).send("Deployment zip not found. Please generate it first.");
  }
});

// Health check endpoint
app.get("/api/health", (req, res) => {
  res.json({
    status: "ok",
    phpRunning: phpProcess !== null && !phpProcess.killed,
    phpBinary: findPhpBinary(),
  });
});

// Reverse proxy all incoming requests to local PHP 8 server
app.use((req, res) => {
  const headers = { ...req.headers };
  delete headers["host"];

  let reqPath = req.originalUrl || req.url;
  // If root requested, ensure index.php is targeted
  if (reqPath === "/" || reqPath === "") {
    reqPath = "/index.php";
  }

  const options: http.RequestOptions = {
    hostname: "127.0.0.1",
    port: PHP_PORT,
    path: reqPath,
    method: req.method,
    headers: {
      ...headers,
      host: `127.0.0.1:${PHP_PORT}`,
      "x-forwarded-for": req.socket.remoteAddress || "127.0.0.1",
    },
  };

  const phpReq = http.request(options, (phpRes) => {
    res.writeHead(phpRes.statusCode || 200, phpRes.headers);
    phpRes.pipe(res);
  });

  phpReq.on("error", (err) => {
    console.error("Proxy error to PHP server:", err.message);
    if (!res.headersSent) {
      res.status(502).send("PHP Server starting up or unavailable. Please refresh in a moment.");
    }
  });

  req.pipe(phpReq);
});

app.listen(PORT, "0.0.0.0", () => {
  console.log(`Node reverse proxy listening on http://0.0.0.0:${PORT} -> forwarding to PHP on port ${PHP_PORT}`);
});
