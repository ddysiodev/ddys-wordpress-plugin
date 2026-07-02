(function (wp) {
  if (!wp || !wp.blocks || !wp.element || !wp.components) {
    return;
  }

  var el = wp.element.createElement;
  var ServerSideRender = wp.serverSideRender && (wp.serverSideRender.default || wp.serverSideRender);
  var TextControl = wp.components.TextControl;
  var SelectControl = wp.components.SelectControl;
  var PanelBody = wp.components.PanelBody;
  var InspectorControls = wp.blockEditor && wp.blockEditor.InspectorControls;

  function register(name, title, attrs) {
    wp.blocks.registerBlockType('ddys/' + name, {
      title: title,
      icon: 'video-alt3',
      category: 'widgets',
      attributes: {
        limit: { type: 'number', default: attrs.limit || 12 },
        layout: { type: 'string', default: attrs.layout || 'grid' },
        slug: { type: 'string', default: attrs.slug || '' }
      },
      edit: function (props) {
        var controls = [];
        if (name === 'latest' || name === 'hot') {
          controls.push(el(TextControl, {
            key: 'limit',
            label: 'Limit',
            type: 'number',
            value: props.attributes.limit,
            onChange: function (value) {
              props.setAttributes({ limit: parseInt(value, 10) || 12 });
            }
          }));
          controls.push(el(SelectControl, {
            key: 'layout',
            label: 'Layout',
            value: props.attributes.layout,
            options: [
              { label: 'Grid', value: 'grid' },
              { label: 'List', value: 'list' },
              { label: 'Compact', value: 'compact' }
            ],
            onChange: function (value) {
              props.setAttributes({ layout: value });
            }
          }));
        }

        if (name === 'movie' || name === 'collection') {
          controls.push(el(TextControl, {
            key: 'slug',
            label: 'Slug',
            value: props.attributes.slug,
            onChange: function (value) {
              props.setAttributes({ slug: value });
            }
          }));
        }

        return el('div', {},
          InspectorControls ? el(InspectorControls, {}, el(PanelBody, { title: 'DDYS' }, controls)) : null,
          ServerSideRender ? el(ServerSideRender, { block: 'ddys/' + name, attributes: props.attributes }) : el('p', {}, title)
        );
      },
      save: function () {
        return null;
      }
    });
  }

  register('latest', 'DDYS Latest', { limit: 12, layout: 'grid' });
  register('hot', 'DDYS Hot', { limit: 10, layout: 'list' });
  register('search', 'DDYS Search', {});
  register('calendar', 'DDYS Calendar', {});
  register('movie', 'DDYS Movie', { slug: '' });
  register('collection', 'DDYS Collection', { slug: '' });
}(window.wp));
