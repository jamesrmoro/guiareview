$( document ).ready(function() {

	let current_url = $("#current_url").val();

	let url_ajax = current_url.replace("amazon-api.php", "");

	function verificarPreenchimento() {
        $(".input-amazon").each(function () {
            if ($(this).val().trim() !== '') {
                $(this).addClass('loading-preenchido');
            } else {
                $(this).removeClass('loading-preenchido');
            }
        });
    }

    $("body").on("click", ".tabs li", function(){
    	id = $(this).attr("data-tab");
    	$(".tabs li").removeClass("active");
    	$(this).addClass("active");
    	$(".tab").removeClass("active");
    	$("#"+id).addClass("active");
    });

	function post_data(array_data){
		let all = array_data;
		let atual = array_data.shift();
		let id_atual = atual.getAttribute('id');

		let dados_post = new FormData();
		dados_post.append("title", $(atual).find('.title-js').val());
		dados_post.append("asin", $(atual).find('.asin-js').val());
		dados_post.append("url_afiliado", $(atual).find('.url-afiliado-js').val());
		dados_post.append("isbn_10", $(atual).find('.isbn_10-js').val());
		dados_post.append("isbn_13", $(atual).find('.isbn_13-js').val());
		dados_post.append("author", $(atual).find('.author-js').val());
		dados_post.append("company", $(atual).find('.company-js').val());
		dados_post.append("dimensions", $(atual).find('.dimensions-js').val());
		dados_post.append("language", $(atual).find('.language-js').val());
		dados_post.append("pages_count", $(atual).find('.pages_count-js').val());
		dados_post.append("formattedDate", $(atual).find('.formattedDate-js').val());
		dados_post.append("image_large", $(atual).find('.image_large-js').val());
		dados_post.append("image_variant_large", $(atual).find('.image_variant_large-js').val());
		dados_post.append("description", $(atual).find('.description-js').val());
		dados_post.append("category_id_wp", $('#category_id_wp').val());
		dados_post.append("tags", $('.tags-js').val());

		for (const pair of dados_post.entries()) {
			console.log(pair[0])
			console.log(pair[1])
		}

		$.ajax({
			type: 'POST',
			url: url_ajax+"/insert_post.php",
			data: dados_post,
			processData: false,
            contentType: false,
            beforeSend: function () {
                console.log("preparando");
                $(".button-send.ok").addClass("wait");
                $(".button-send.ok").removeClass("completed");
                $(".button-send.ok.wait span").text("Gravando no WP");
            }
		}).done(function(e) {
			try {
                // Tenta fazer o parse da resposta JSON
                let jsonResponse = JSON.parse(e);
                console.log(jsonResponse);
                
                // Verifica se ainda há mais dados no array e processa o próximo
                if(all.length > 0) {
                    post_data(all);
                }

                // Atualizações de UI
                verificarPreenchimento();
				console.log("enviado no wp");
				$(".button-send.ok").removeClass("wait");
				$(".button-send.ok").addClass("completed");
				$(".button-send.ok span").text("Posts criados :)");
				$('[data-tab="'+id_atual+'"]').addClass('success');

            } catch (error) {
                // Caso o parse falhe, exibe o erro e a resposta completa
                console.error("Erro ao fazer o parse do JSON:", error);
                console.log("Resposta completa:", e);
            }
		});
	}

    $("body").on("click", ".button-send", function(e){
		e.preventDefault();
		verificarPreenchimento();
		let post_array = Array.from($('form div.tab'));
		console.log(post_array);
		post_data(post_array)
    });

});
