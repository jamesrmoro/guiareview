$( document ).ready(function() {
    $("body").on("click", ".tabs li", function(){
    	id = $(this).attr("data-tab");
    	$(".tabs li").removeClass("active");
    	$(this).addClass("active");
    	$(".tab").removeClass("active");
    	$("#"+id).addClass("active");
    });

	function post_data(array_data){
		let all = array_data
		let atual = array_data.shift();
		let id_atual = atual.getAttribute('id');

		let dados_post = new FormData();
		dados_post.append("url", $(atual).find('.url-afiliado-js').val());
		dados_post.append("title", $(atual).find('.title-js').val());
		dados_post.append("imagem_url", $(atual).find('.image-js').val());
		dados_post.append("author", $(atual).find('.author-js').val());
		dados_post.append("text", $(atual).find('.text-js').val());
		dados_post.append("category", $('.category-js').val());
		dados_post.append("tags", $('.tags-js').val());

		let input_book_attributes = $('#'+id_atual+' .books-attributes input')
		let label_book_attributes = $('#'+id_atual+' .books-attributes label')
		label_book_attributes.each(function( index ) {
			let title = $( this ).text()
			if(title == 'Número de páginas'){
				dados_post.append("pages", input_book_attributes[index].value);
			}
			if(title == 'Idioma'){
				dados_post.append("language", input_book_attributes[index].value);
			}
			if(title == 'Editora'){
				dados_post.append("company", input_book_attributes[index].value);
			}
			if(title == 'Data da publicação'){
				dados_post.append("date_published", input_book_attributes[index].value);
			}
			if(title == 'Tamanho do arquivo'){
				dados_post.append("file_size", input_book_attributes[index].value);
			}
			if(title == 'Page Flip'){
				dados_post.append("page_flip", input_book_attributes[index].value);
			}
			if(title == 'Dicas de vocabulário'){
				dados_post.append("vocabulary_tips", input_book_attributes[index].value);
			}
			if(title == 'Configuração de fonte'){
				dados_post.append("font_configuration", input_book_attributes[index].value);
			}
			if(title == 'Dicas de vocabulário'){
				dados_post.append("vocabulary_tips", input_book_attributes[index].value);
			}
			if(title == 'ISBN-10'){
				dados_post.append("isbn", input_book_attributes[index].value);
			}
			if(title == 'ISBN-13'){
				dados_post.append("isbn_13", input_book_attributes[index].value);
			}
			if(title == 'Dimensões'){
				dados_post.append("measurements", input_book_attributes[index].value);
			}
		});
		$.ajax({
			type: 'POST',
			url: "https://os10melhoreslivros.com.br/wp-content/themes/os10melhoreslivros/amazon/insert_post.php",
			data: dados_post,
			processData: false,
            contentType: false
		}).done(function(e) {
			console.log(JSON.parse(e))
			console.log(all)
			console.log(atual)
			$('[data-tab="'+id_atual+'"]').css('background-color', 'green')
			$('[data-tab="'+id_atual+'"]').css('color', 'white')
			if(all.length>0){
				post_data(all);
			}
		});
	}

    $("body").on("click", "button", function(e){
		e.preventDefault();
		let post_array = Array.from($('form div.tab'))
		post_data(post_array)
    });

});