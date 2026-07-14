$(document).ready(function () {

    function verificarPreenchimento() {
        $(".input-amazon").each(function () {
            if ($(this).val().trim() !== '') {
                $(this).addClass('loading-preenchido');
            } else {
                $(this).removeClass('loading-preenchido');
            }
        });
    }

    $("body").on("click", ".button-load-js", function (e) {
        e.preventDefault();

        $(this).closest(".field-input").addClass("opacity");

        $(".button-send.start").removeClass("show");
        $(".button-send.sending").removeClass("show");
        let asin = [];
        for (i = 1; i <= 10; i++) {
            asin.push($("#url_page_amazon_" + i).val());
        }

        // Chamando verificarPreenchimento antes da requisição AJAX
        verificarPreenchimento();

        $.ajax({
            type: 'POST',
            data: {
                asin: JSON.stringify(asin)
            },
            url: "amazon-request.php",
            beforeSend: function () {
                $(".loading").addClass("show");
                $(".button-send.start").addClass("hide");
                $(".button-send.sending").removeClass("hide");
                $(".button-send.ok").removeClass("hide");
            }
        }).done(function (e) {
            let data = JSON.parse(e);
            console.log(data);

            $("#group-wp").removeClass("opacity");

            // Chamando verificarPreenchimento após a requisição AJAX

            $(".button-send.start").addClass("hide");
            $(".button-send.sending").addClass("hide");
            $(".button-send.ok").addClass("show");
            $(".loading").removeClass("show");

            data.forEach((item, key) => {
                console.log(item);
                console.log(key);

                $("#infoLinkGoogle_" + (key + 1)).val(item.url_google);
                $("#title_" + (key + 1)).val(item.title);
                $("#url_affilliate_" + (key + 1)).val(item.url_affilliate);
                $("#isbn_10_" + (key + 1)).val(item.isbn_10);
                $("#isbn_13_" + (key + 1)).val(item.isbn_13);
                $("#author_" + (key + 1)).val(item.author);
                $("#company_" + (key + 1)).val(item.company);
                $("#dimensions_" + (key + 1)).val(item.dimensions);
                $("#language_" + (key + 1)).val(item.language);
                $("#pages_count_" + (key + 1)).val(item.pages_count);
                $("#formattedDate_" + (key + 1)).val(item.published_date);
                $("#image_large_" + (key + 1)).val(item.image_large);
                $("#image_variant_large_" + (key + 1)).val(item.image_variant_large);
                $("#description_" + (key + 1)).val(item.description);

                if (item.image_variant_large.trim() !== '') {
                    $("#url_image2_preview_" + (key + 1)).attr("src", item.image_variant_large);
                } else {
                    $("#url_image2_preview_" + (key + 1)).attr("src", "images/image-default.png");
                }

                $("#url_image_preview_" + (key + 1)).attr("src", item.image_large);
            });

            verificarPreenchimento();
        });
    });
});
