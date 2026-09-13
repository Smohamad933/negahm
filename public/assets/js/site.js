/* =====================================================================
   نگاه مدیا | Negah Media — اسکریپت‌های سمت سایت
   ===================================================================== */
(function () {
  'use strict';

  var doc = document;

  /* ---------------------------------------------------- منوی موبایل */
  var toggle = doc.querySelector('[data-nav-toggle]');
  var header = doc.querySelector('[data-header]');
  var nav = doc.querySelector('[data-nav]');
  var shell = header || nav;

  if (toggle && shell) {
    var panel = doc.querySelector('.mobile-nav') || nav;
    if (panel) {
      panel.id = panel.id || 'site-nav';
      toggle.setAttribute('aria-controls', panel.id);
    }

    var close = function () {
      shell.classList.remove('is-open');
      doc.body.classList.remove('menu-open');
      toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', function () {
      var open = !shell.classList.contains('is-open');
      shell.classList.toggle('is-open', open);
      doc.body.classList.toggle('menu-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    doc.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        close();
      }
    });

    // با کلیک روی هر پیوند منو، منوی موبایل بسته شود
    if (panel) {
      panel.addEventListener('click', function (event) {
        if (event.target.closest('a')) {
          close();
        }
      });
    }
  }

  /* --------------------------------------------- سایه هدر هنگام اسکرول */
  var toTop = doc.querySelector('[data-to-top]');

  function onScroll() {
    var y = window.scrollY || window.pageYOffset || 0;
    if (header) {
      header.classList.toggle('is-scrolled', y > 12);
    }
    if (toTop) {
      toTop.classList.toggle('is-visible', y > 600);
    }
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  if (toTop) {
    toTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* --------------------------------------------- انیمیشن ظاهر شدن بخش‌ها */
  var revealables = doc.querySelectorAll('[data-reveal]');

  if ('IntersectionObserver' in window && revealables.length) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }
        entry.target.classList.add('is-in');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    revealables.forEach(function (el) { observer.observe(el); });
  } else {
    revealables.forEach(function (el) { el.classList.add('is-in'); });
  }

  /* --------------------------------------------------- شمارنده آمار */
  function animateCounter(el) {
    var raw = String(el.getAttribute('data-count') || el.textContent || '');
    var persian = { '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9' };
    var normalized = raw.replace(/[۰-۹٠-٩]/g, function (d) { return persian[d] !== undefined ? persian[d] : d; });
    var match = normalized.match(/\d+(\.\d+)?/);

    if (!match) {
      return;
    }

    var target = parseFloat(match[0]);
    var prefix = normalized.slice(0, normalized.indexOf(match[0]));
    var suffix = normalized.slice(normalized.indexOf(match[0]) + match[0].length);
    var start = null;
    var duration = 1400;

    function toPersian(value) {
      var map = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
      return String(value).replace(/\d/g, function (d) { return map[d]; });
    }

    function step(timestamp) {
      if (start === null) {
        start = timestamp;
      }
      var progress = Math.min((timestamp - start) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      var current = Math.round(target * eased);
      el.textContent = prefix + toPersian(current) + suffix;
      if (progress < 1) {
        window.requestAnimationFrame(step);
      }
    }

    window.requestAnimationFrame(step);
  }

  var counters = doc.querySelectorAll('[data-count]');
  if ('IntersectionObserver' in window && counters.length) {
    var counterObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          counterObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.4 });
    counters.forEach(function (el) { counterObserver.observe(el); });
  }

  /* ------------------------------------------------- بسته شدن هشدارها */
  doc.addEventListener('click', function (event) {
    var button = event.target.closest('.alert-close');
    if (button) {
      var alert = button.closest('.alert');
      if (alert) {
        alert.remove();
      }
    }
  });

  /* ------------------------------------------- عضویت در خبرنامه (AJAX) */
  var subscribe = doc.querySelector('[data-subscribe]');
  if (subscribe) {
    subscribe.addEventListener('submit', function (event) {
      event.preventDefault();
      var note = subscribe.querySelector('[data-subscribe-note]');
      var input = subscribe.querySelector('input[type="email"]');
      var button = subscribe.querySelector('button');

      if (!input || !input.value) {
        return;
      }

      button.disabled = true;
      if (note) {
        note.textContent = 'در حال ارسال…';
      }

      var payload = new FormData(subscribe);

      fetch(subscribe.action, {
        method: 'POST',
        body: payload,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          if (note) {
            note.textContent = data.message || '';
          }
          if (data.ok && input) {
            input.value = '';
          }
        })
        .catch(function () {
          if (note) {
            note.textContent = 'ارسال ناموفق بود؛ لطفاً دوباره تلاش کنید.';
          }
        })
        .finally(function () {
          button.disabled = false;
        });
    });
  }

  /* --------------------------------------------- ارسال فرم تماس (AJAX) */
  var contactForm = doc.querySelector('.contact-form');
  if (contactForm) {
    contactForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var button = contactForm.querySelector('button[type="submit"]');
      var original = button ? button.innerHTML : '';

      if (button) {
        button.disabled = true;
        button.innerHTML = 'در حال ارسال…';
      }

      var payload = new FormData(contactForm);

      fetch(contactForm.action, {
        method: 'POST',
        body: payload,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          if (data.ok) {
            contactForm.innerHTML =
              '<div class="alert alert-success" role="alert"><span class="alert-icon">✦</span><div>' +
              (data.message || 'پیام شما ثبت شد.') +
              '</div></div>';
            return;
          }

          var errors = data.errors || {};
          var message = data.message || 'ارسال ناموفق بود.';

          var box = doc.createElement('div');
          box.className = 'alert alert-danger';
          box.setAttribute('role', 'alert');
          box.innerHTML = '<span class="alert-icon">✦</span><div>' + message +
            '<ul style="margin:.4rem 1.2rem 0">' +
            Object.keys(errors).map(function (key) { return '<li>' + errors[key] + '</li>'; }).join('') +
            '</ul></div>';

          var previous = contactForm.querySelector('.alert');
          if (previous) {
            previous.remove();
          }
          contactForm.insertBefore(box, contactForm.firstChild);
          box.scrollIntoView({ behavior: 'smooth', block: 'center' });

          if (button) {
            button.disabled = false;
            button.innerHTML = original;
          }
        })
        .catch(function () {
          if (button) {
            button.disabled = false;
            button.innerHTML = original;
          }
          contactForm.submit();
        });
    });
  }

  /* --------------------------------------------------- لایت‌باکس گالری */
  var overlay = doc.querySelector('[data-lightbox-overlay]');
  if (overlay) {
    var img = overlay.querySelector('[data-lightbox-img]');
    var caption = overlay.querySelector('[data-lightbox-caption]');

    function close() {
      overlay.hidden = true;
      doc.body.style.overflow = '';
    }

    doc.querySelectorAll('[data-lightbox] .gallery-item').forEach(function (item) {
      item.addEventListener('click', function (event) {
        event.preventDefault();
        if (img) {
          img.src = item.getAttribute('href');
          img.alt = item.getAttribute('data-caption') || '';
        }
        if (caption) {
          caption.textContent = item.getAttribute('data-caption') || '';
        }
        overlay.hidden = false;
        doc.body.style.overflow = 'hidden';
      });
    });

    var closeButton = overlay.querySelector('[data-lightbox-close]');
    if (closeButton) {
      closeButton.addEventListener('click', close);
    }
    overlay.addEventListener('click', function (event) {
      if (event.target === overlay) {
        close();
      }
    });
    doc.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !overlay.hidden) {
        close();
      }
    });
  }

  /* ----------------------------------------- سال خودکار در فوتر (اختیاری) */
  doc.querySelectorAll('[data-current-year]').forEach(function (el) {
    el.textContent = String(new Date().getFullYear());
  });
})();
