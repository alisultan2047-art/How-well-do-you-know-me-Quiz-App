/**
 * "How Well Do You Know Ali?" - Core Interactive Client Logic
 * Mobile-friendly, zero external dependencies, ultra-lightweight.
 */

document.addEventListener('DOMContentLoaded', () => {
  initQuizApp();
  initResultPage();
  initAdminDashboard();
});

/**
 * Quiz Navigation and State Handling
 */
function initQuizApp() {
  const quizForm = document.getElementById('quizForm');
  if (!quizForm) return;

  const questions = document.querySelectorAll('.question-step');
  if (questions.length === 0) return;

  const totalQuestions = questions.length;
  let currentIndex = 0;
  const userAnswers = {}; // Map question_id -> 'A' | 'B' | 'C' | 'D'

  const prevBtn = document.getElementById('prevQuestionBtn');
  const nextBtn = document.getElementById('nextQuestionBtn');
  const submitBtn = document.getElementById('submitQuizBtn');
  const currentNumEl = document.getElementById('currentQuestionNumber');
  const progressFill = document.getElementById('progressFill');
  const validationHint = document.getElementById('validationHint');

  // Confirmation Modal Elements
  const submitModal = document.getElementById('submitModal');
  const cancelSubmitBtn = document.getElementById('cancelSubmitBtn');
  const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');

  // Attach option click listeners
  questions.forEach((qEl) => {
    const qId = qEl.getAttribute('data-question-id');
    const optionBtns = qEl.querySelectorAll('.option-btn');

    optionBtns.forEach((btn) => {
      btn.addEventListener('click', () => {
        const optionKey = btn.getAttribute('data-option');
        
        // Deselect siblings
        optionBtns.forEach(b => b.classList.remove('selected'));
        // Select this option
        btn.classList.add('selected');
        userAnswers[qId] = optionKey;

        // Hide validation warning if visible
        if (validationHint) {
          validationHint.style.display = 'none';
        }

        // Add subtle haptic vibration on supported mobile devices
        if ('vibrate' in navigator) {
          try { navigator.vibrate(12); } catch(e) {}
        }
      });
    });
  });

  // Display question by index
  function showQuestion(index) {
    questions.forEach((q, i) => {
      q.style.display = (i === index) ? 'block' : 'none';
    });

    // Update Question counter
    if (currentNumEl) {
      currentNumEl.textContent = (index + 1);
    }

    // Update Progress Bar
    if (progressFill) {
      const percent = Math.round(((index + 1) / totalQuestions) * 100);
      progressFill.style.width = `${percent}%`;
    }

    // Previous Button state
    if (prevBtn) {
      prevBtn.disabled = (index === 0);
    }

    // Toggle Next vs Submit Button
    const isLast = (index === totalQuestions - 1);
    if (nextBtn) {
      nextBtn.style.display = isLast ? 'none' : 'inline-flex';
    }
    if (submitBtn) {
      submitBtn.style.display = isLast ? 'inline-flex' : 'none';
    }

    // Hide validation hint
    if (validationHint) {
      validationHint.style.display = 'none';
    }
  }

  // Navigation: Previous
  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      if (currentIndex > 0) {
        currentIndex--;
        showQuestion(currentIndex);
      }
    });
  }

  // Navigation: Next
  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      const currentQ = questions[currentIndex];
      const qId = currentQ.getAttribute('data-question-id');

      // Check if user answered current question
      if (!userAnswers[qId]) {
        showValidationShake(currentQ);
        return;
      }

      if (currentIndex < totalQuestions - 1) {
        currentIndex++;
        showQuestion(currentIndex);
      }
    });
  }

  // Submit Trigger
  if (submitBtn) {
    submitBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const currentQ = questions[currentIndex];
      const qId = currentQ.getAttribute('data-question-id');

      if (!userAnswers[qId]) {
        showValidationShake(currentQ);
        return;
      }

      // Check if all questions are answered
      let unansweredCount = 0;
      questions.forEach((q) => {
        const id = q.getAttribute('data-question-id');
        if (!userAnswers[id]) unansweredCount++;
      });

      if (unansweredCount > 0) {
        alert(`You still have ${unansweredCount} unanswered question(s). Please review your answers.`);
        return;
      }

      // Open confirmation modal
      if (submitModal) {
        submitModal.classList.add('active');
      } else {
        performSubmit();
      }
    });
  }

  // Modal Cancel
  if (cancelSubmitBtn && submitModal) {
    cancelSubmitBtn.addEventListener('click', () => {
      submitModal.classList.remove('active');
    });
  }

  // Modal Confirm
  if (confirmSubmitBtn) {
    confirmSubmitBtn.addEventListener('click', () => {
      confirmSubmitBtn.disabled = true;
      confirmSubmitBtn.textContent = 'Calculating score...';
      performSubmit();
    });
  }

  // Show friendly shake
  function showValidationShake(element) {
    element.classList.remove('shake');
    // Force reflow
    void element.offsetWidth;
    element.classList.add('shake');

    if (validationHint) {
      validationHint.style.display = 'block';
    }
  }

  // Populates hidden inputs and submits the form
  function performSubmit() {
    // Inject selected answers as hidden inputs into quizForm
    for (const [qId, option] of Object.entries(userAnswers)) {
      let input = document.getElementById(`answer_input_${qId}`);
      if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.id = `answer_input_${qId}`;
        input.name = `answers[${qId}]`;
        quizForm.appendChild(input);
      }
      input.value = option;
    }

    quizForm.submit();
  }

  // Initial show
  showQuestion(0);
}

/**
 * Result Page Share & Visual Counter
 */
function initResultPage() {
  const resultContainer = document.querySelector('.result-view-container');
  if (!resultContainer) return;

  // Animate score counter
  const scoreEl = document.getElementById('animatedScoreVal');
  if (scoreEl) {
    const finalScore = parseInt(scoreEl.getAttribute('data-score') || '0', 10);
    let currentVal = 0;
    const duration = 1200;
    const stepTime = Math.max(20, Math.floor(duration / (finalScore + 1)));

    const timer = setInterval(() => {
      if (currentVal >= finalScore) {
        scoreEl.textContent = finalScore;
        clearInterval(timer);
      } else {
        currentVal++;
        scoreEl.textContent = currentVal;
      }
    }, stepTime);
  }

  // Copy Link Button
  const copyBtn = document.getElementById('copyQuizLinkBtn');
  const toast = document.getElementById('linkToast');

  if (copyBtn) {
    copyBtn.addEventListener('click', async () => {
      // Get base URL for the quiz
      const quizUrl = copyBtn.getAttribute('data-url') || window.location.origin + window.location.pathname.replace('result.php', 'index.php');
      
      try {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(quizUrl);
        } else {
          // Fallback
          const tempInput = document.createElement('input');
          tempInput.value = quizUrl;
          document.body.appendChild(tempInput);
          tempInput.select();
          document.execCommand('copy');
          document.body.removeChild(tempInput);
        }

        // Show toast
        if (toast) {
          toast.classList.add('show');
          setTimeout(() => toast.classList.remove('show'), 2500);
        }
      } catch (err) {
        console.error('Failed to copy link:', err);
        prompt('Copy this link to challenge friends:', quizUrl);
      }
    });
  }
}

/**
 * Admin Dashboard Helpers (Search & Filters)
 */
function initAdminDashboard() {
  const searchInput = document.getElementById('responseSearchInput');
  const table = document.getElementById('responsesTable');

  if (searchInput && table) {
    searchInput.addEventListener('input', () => {
      const filter = searchInput.value.toLowerCase().trim();
      const rows = table.querySelectorAll('tbody tr');

      rows.forEach(row => {
        const nameCell = row.querySelector('.friend-name-cell');
        if (nameCell) {
          const name = nameCell.textContent.toLowerCase();
          row.style.display = name.includes(filter) ? '' : 'none';
        }
      });
    });
  }
}
