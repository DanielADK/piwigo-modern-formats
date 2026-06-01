(function () {
  var cfg = window.MF_BULK;
  var btn = document.getElementById('mfBulkStart');
  if (!btn) return;

  var wrap = document.getElementById('mfProgressWrap');
  var bar = document.getElementById('mfProgressBar');
  var status = document.getElementById('mfBulkStatus');
  var pending = document.getElementById('mfPending');
  var total = cfg.total || 0;
  var errorCount = 0;

  function setProgress(remaining) {
    var doneCount = Math.max(0, total - remaining);
    var pct = total > 0 ? Math.round((doneCount / total) * 100) : 100;
    bar.style.width = pct + '%';
    if (pending) pending.textContent = remaining;
  }

  function step(startId) {
    var body = new FormData();
    body.append('method', 'pwg.modernFormats.convert');
    body.append('limit', '50');
    body.append('pwg_token', cfg.token);
    if (startId) body.append('start_id', String(startId));

    fetch(cfg.wsUrl, { method: 'POST', body: body, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j || j.stat !== 'ok') { throw new Error('ws error'); }
        var res = j.result;
        if (res.errors && res.errors.length) { errorCount += res.errors.length; }
        setProgress(res.remaining);
        if (res.next_id) {
          setTimeout(function () { step(res.next_id); }, 50);
        } else {
          status.textContent = errorCount > 0 ? cfg.i18n.doneErrors : cfg.i18n.done;
          btn.disabled = false;
        }
      })
      .catch(function () {
        status.textContent = cfg.i18n.failed;
        btn.disabled = false;
      });
  }

  btn.addEventListener('click', function () {
    btn.disabled = true;
    wrap.style.display = 'block';
    status.textContent = cfg.i18n.running;
    step(0);
  });
})();
