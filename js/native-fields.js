jQuery(function ($) {
    $('.grv-media').on('click', function () {
        const target = $(this).data('target');
        const multiple = $(this).data('multiple') === 1;
        const frame = wp.media({ title: 'Selecionar imagens do produto', library: { type: 'image' }, multiple: multiple });
        frame.on('open', function () {
            $('#' + target).val().split(',').forEach(function (id) {
                if (/^\d+$/.test(id) && Number(id)) {
                    const attachment = wp.media.attachment(Number(id));
                    attachment.fetch();
                    frame.state().get('selection').add(attachment);
                }
            });
        });
        frame.on('select', function () {
            const images = frame.state().get('selection').toJSON();
            $('#' + target).val(images.map(function (image) { return image.id; }).join(','));
            const preview = $('#' + target + '-preview').empty();
            images.forEach(function (image) {
                $('<img>', { src: image.sizes && image.sizes.thumbnail ? image.sizes.thumbnail.url : image.url, alt: image.alt || '', width: 100 }).appendTo(preview);
            });
        });
        frame.open();
    });
    $('.grv-media-clear').on('click', function () {
        const target = $(this).data('target');
        $('#' + target).val('');
        $('#' + target + '-preview').empty();
    });
});
