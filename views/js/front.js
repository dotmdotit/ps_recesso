/* dotmrecesso — modulo pubblico: righe prodotto visibili solo per il recesso parziale, aggiungi/rimuovi riga. */
(function () {
  function init() {
    var form = document.getElementById('dmr-public');
    var box = document.getElementById('dmr-rows');
    if (!form || !box) {
      return;
    }
    var add = box.querySelector('.dmr-row-add');
    var template = box.querySelector('.dmr-row').cloneNode(true);
    Array.prototype.forEach.call(template.querySelectorAll('input'), function (input) {
      input.value = '';
    });

    function rows() {
      return box.querySelectorAll('.dmr-row');
    }

    function refresh() {
      var list = rows();
      Array.prototype.forEach.call(list, function (row) {
        row.querySelector('.dmr-row-remove').hidden = list.length < 2;
      });
    }

    function toggle() {
      var partial = form.querySelector('input[name="dmr_scope"]:checked');
      box.hidden = !partial || partial.value !== 'partial';
    }

    // senza JavaScript restano le righe vuote predisposte: qui si parte da quelle compilate (minimo una)
    Array.prototype.forEach.call(rows(), function (row, i) {
      var filled = Array.prototype.some.call(row.querySelectorAll('input'), function (input) {
        return input.value !== '';
      });
      if (!filled && i > 0) {
        row.parentNode.removeChild(row);
      }
    });

    add.hidden = false;
    add.addEventListener('click', function () {
      var row = template.cloneNode(true);
      box.insertBefore(row, add);
      refresh();
      row.querySelector('input').focus();
    });
    box.addEventListener('click', function (e) {
      if (e.target.classList.contains('dmr-row-remove') && rows().length > 1) {
        var row = e.target.closest('.dmr-row');
        row.parentNode.removeChild(row);
        refresh();
      }
    });
    Array.prototype.forEach.call(form.querySelectorAll('input[name="dmr_scope"]'), function (radio) {
      radio.addEventListener('change', toggle);
    });

    refresh();
    toggle();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
