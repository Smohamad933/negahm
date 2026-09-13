/* =====================================================================
   نگاه مدیا | Negah Media — اسکریپت‌های پنل مدیریت
   ===================================================================== */
(function () {
  'use strict';

  var doc = document;
  var config = window.NEGAHM || { csrf: '', base: '/' };

  /* --------------------------------------------------- منوی کناری */
  var sidebar = doc.querySelector('[data-sidebar]');
  var sidebarToggle = doc.querySelector('[data-sidebar-toggle]');
  var sidebarClose = doc.querySelector('[data-sidebar-close]');

  function closeSidebar() {
    if (sidebar) {
      sidebar.classList.remove('is-open');
    }
  }

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function () {
      sidebar.classList.toggle('is-open');
    });
  }
  if (sidebarClose) {
    sidebarClose.addEventListener('click', closeSidebar);
  }
  doc.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeSidebar();
    }
  });

  /* --------------------------------------- تایید پیش از ارسال فرم حذف */
  doc.addEventListener('submit', function (event) {
    var form = event.target;
    var message = form.getAttribute && form.getAttribute('data-confirm');
    if (message && !window.confirm(message)) {
      event.preventDefault();
    }
  });

  /* ------------------------------------------------ بستن هشدارها */
  doc.addEventListener('click', function (event) {
    var button = event.target.closest('.alert-close');
    if (button) {
      var alert = button.closest('.alert');
      if (alert) {
        alert.remove();
      }
    }
  });

  /* ----------------------------------------- نمایش/پنهان کردن رمز */
  doc.querySelectorAll('[data-password-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
      var input = button.parentElement.querySelector('input');
      if (!input) {
        return;
      }
      input.type = input.type === 'password' ? 'text' : 'password';
      button.textContent = input.type === 'password' ? '◉' : '◌';
    });
  });

  /* ------------------------------- کلیدهای انتشار/ویژه (AJAX toggle) */
  doc.addEventListener('click', function (event) {
    var button = event.target.closest('[data-toggle]');
    if (!button) {
      return;
    }

    var endpoint = button.getAttribute('data-url');
    if (!endpoint) {
      return;
    }

    button.classList.add('is-loading');

    var payload = new FormData();
    payload.append('_token', config.csrf);

    fetch(endpoint, {
      method: 'POST',
      body: payload,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data.ok) {
          var on = Number(data.value) === 1;
          button.classList.toggle('is-on', on);
          button.setAttribute('aria-pressed', on ? 'true' : 'false');
        } else {
          notify(data.message || 'تغییر وضعیت ناموفق بود.', 'danger');
        }
      })
      .catch(function () {
        notify('ارتباط با سرور برقرار نشد.', 'danger');
      })
      .finally(function () {
        button.classList.remove('is-loading');
      });
  });

  /* ------------------------------------------- ساخت نامک از عنوان */
  var persianDigits = { '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9' };

  function slugify(value) {
    return String(value)
      .replace(/[۰-۹٠-٩]/g, function (d) { return persianDigits[d] !== undefined ? persianDigits[d] : d; })
      .replace(/ي/g, 'ی')
      .replace(/ك/g, 'ک')
      .trim()
      .replace(/[^\p{L}\p{N}]+/gu, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '')
      .toLowerCase();
  }

  var titleInput = doc.querySelector('#title, #name');
  var slugInput = doc.querySelector('#slug');

  if (titleInput && slugInput && slugInput.value === '') {
    var touched = false;
    slugInput.addEventListener('input', function () { touched = true; });
    titleInput.addEventListener('input', function () {
      if (!touched) {
        slugInput.value = slugify(titleInput.value);
      }
    });
  }

  /* ------------------------------------------------- فیلد تکرارشونده */
  doc.querySelectorAll('[data-repeater]').forEach(function (repeater) {
    var addButton = repeater.querySelector('[data-repeater-add]');

    if (addButton) {
      addButton.addEventListener('click', function () {
        var template = addButton.getAttribute('data-template');
        if (!template) {
          return;
        }
        var wrapper = doc.createElement('div');
        wrapper.innerHTML = template.trim();
        repeater.insertBefore(wrapper.firstChild, addButton);
      });
    }

    repeater.addEventListener('click', function (event) {
      var removeButton = event.target.closest('[data-repeater-remove]');
      if (removeButton) {
        var row = removeButton.closest('.repeater-row');
        if (row) {
          row.remove();
        }
      }
    });
  });

  /* ------------------------------------------- ویرایش کاربر (جدول) */
  var userEditForm = doc.querySelector('[data-user-edit-form]');
  if (userEditForm) {
    doc.querySelectorAll('[data-edit-user]').forEach(function (button) {
      button.addEventListener('click', function () {
        var id = button.getAttribute('data-id');
        var base = (config.base || '/').replace(/\/$/, '');

        userEditForm.action = base + '/admin/users/' + id;
        userEditForm.querySelector('#edit_name').value = button.getAttribute('data-name') || '';
        userEditForm.querySelector('#edit_email').value = button.getAttribute('data-email') || '';
        userEditForm.querySelector('#edit_role').value = button.getAttribute('data-role') || 'editor';
        userEditForm.querySelector('#edit_active').checked = button.getAttribute('data-active') === '1';

        userEditForm.querySelectorAll('input, select, button').forEach(function (field) {
          field.disabled = false;
        });

        userEditForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    });
  }

  /* ----------------------------------------- کپی آدرس فایل رسانه‌ها */
  doc.addEventListener('click', function (event) {
    var button = event.target.closest('[data-copy]');
    if (!button) {
      return;
    }

    var value = button.getAttribute('data-copy');
    var original = button.textContent;

    var done = function () {
      button.textContent = 'کپی شد ✓';
      window.setTimeout(function () { button.textContent = original; }, 1600);
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(value).then(done).catch(function () { fallbackCopy(value, done); });
    } else {
      fallbackCopy(value, done);
    }
  });

  function fallbackCopy(value, done) {
    var field = doc.createElement('textarea');
    field.value = value;
    field.setAttribute('readonly', '');
    field.style.position = 'absolute';
    field.style.left = '-9999px';
    doc.body.appendChild(field);
    field.select();
    try {
      doc.execCommand('copy');
      done();
    } catch (error) {
      notify('کپی آدرس ناموفق بود.', 'danger');
    }
    doc.body.removeChild(field);
  }

  /* ---------------------------------------- بارگذاری رسانه (AJAX) */
  var uploadForm = doc.querySelector('[data-upload]');
  if (uploadForm) {
    uploadForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var note = doc.querySelector('[data-upload-note]');
      var button = uploadForm.querySelector('button[type="submit"]');
      var fileInput = uploadForm.querySelector('input[type="file"]');

      if (!fileInput || !fileInput.files.length) {
        if (note) {
          note.textContent = 'ابتدا فایلی را انتخاب کنید.';
        }
        return;
      }

      button.disabled = true;
      if (note) {
        note.textContent = 'در حال بارگذاری ' + fileInput.files.length + ' فایل…';
      }

      fetch(uploadForm.action, {
        method: 'POST',
        body: new FormData(uploadForm),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          if (note) {
            note.textContent = data.message || '';
          }
          if (data.ok) {
            window.setTimeout(function () { window.location.reload(); }, 700);
          } else if (button) {
            button.disabled = false;
          }
        })
        .catch(function () {
          if (note) {
            note.textContent = 'بارگذاری ناموفق بود؛ لطفاً دوباره تلاش کنید.';
          }
          if (button) {
            button.disabled = false;
          }
        });
    });
  }

  /* --------------------------------------------------- اعلان‌ها */
  function notify(message, type) {
    var zone = doc.querySelector('.flash-zone');
    if (!zone) {
      return;
    }
    var box = doc.createElement('div');
    box.className = 'alert alert-' + (type || 'info');
    box.setAttribute('role', 'alert');
    box.innerHTML = '<span class="alert-icon">✦</span><div></div><button type="button" class="alert-close" aria-label="بستن">×</button>';
    box.querySelector('div').textContent = message;
    zone.appendChild(box);
    window.setTimeout(function () { box.remove(); }, 6000);
  }

  /* --------------------------------- هشدار خروج با تغییرات ذخیره‌نشده */
  var dirty = false;
  doc.querySelectorAll('.form-grid form, .settings-form').forEach(function (form) {
    form.addEventListener('input', function () { dirty = true; });
    form.addEventListener('submit', function () { dirty = false; });
  });

  window.addEventListener('beforeunload', function (event) {
    if (dirty) {
      event.preventDefault();
      event.returnValue = '';
    }
  });
})();
