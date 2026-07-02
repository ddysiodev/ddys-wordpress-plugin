(function () {
  function byId(id) {
    return document.getElementById(id);
  }

  function attr(name, value) {
    if (!value) {
      return '';
    }
    return ' ' + name + '="' + String(value).replace(/"/g, '&quot;') + '"';
  }

  function buildShortcode() {
    var kind = byId('ddys-wp-shortcode-kind');
    var output = byId('ddys-wp-shortcode-output');
    if (!kind || !output) {
      return;
    }

    var tag = kind.value || 'ddys_latest';
    var slug = byId('ddys-wp-shortcode-slug').value.trim();
    var id = byId('ddys-wp-shortcode-id').value.trim();
    var type = byId('ddys-wp-shortcode-type').value.trim();
    var limit = byId('ddys-wp-shortcode-limit').value.trim();
    var perPage = byId('ddys-wp-shortcode-per-page').value.trim();
    var layout = byId('ddys-wp-shortcode-layout').value;

    var code = '[' + tag;
    code += attr('slug', slug);
    code += attr('id', id);
    code += attr('type', type);

    if (tag === 'ddys_latest' || tag === 'ddys_hot') {
      code += attr('limit', limit);
    }

    if (tag !== 'ddys_movie' && tag !== 'ddys_sources' && tag !== 'ddys_share') {
      code += attr('per_page', perPage);
    }

    code += attr('layout', layout);
    code += ']';

    output.value = code;
  }

  document.addEventListener('click', function (event) {
    if (event.target && event.target.id === 'ddys-wp-shortcode-build') {
      buildShortcode();
    }

    if (event.target && event.target.id === 'ddys-wp-shortcode-copy') {
      var output = byId('ddys-wp-shortcode-output');
      if (output && navigator.clipboard) {
        navigator.clipboard.writeText(output.value);
      }
    }
  });

  document.addEventListener('change', function (event) {
    if (event.target && event.target.id && event.target.id.indexOf('ddys-wp-shortcode-') === 0) {
      buildShortcode();
    }
  });
}());
