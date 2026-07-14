document.addEventListener("DOMContentLoaded", async function () {
  let tabs = await chrome.tabs.query({ active: true, currentWindow: true });
  let tabAtual = tabs[0].url;

  document.getElementById("currentUrl").innerText = tabAtual;

  let url = await chrome.storage.local.get(["cachedUrl", "count"]);
  let urlSalvas = (url["cachedUrl"] && url["cachedUrl"].length > 0) ? JSON.parse(url["cachedUrl"]) : [];
  let count = (url["count"] !== undefined) ? parseInt(url["count"]) : 0;

  if (urlSalvas.length > 0) {
    urlSalvas.forEach(element => {
      let liNew = createListItem(element);
      document.querySelector('.list-url').append(liNew);
  });
}

updateCountUI(count);

  // Associar eventos de botões
  document.getElementById("copyButton").addEventListener("click", copyToClipboard);

  document.getElementById("getStoredUrlButton").addEventListener("click", async function () {

    if (!isAmazonURL(tabAtual)) {
      alert("Esta ação só está disponível em páginas da Amazon.");
      return;
    }
    if(count==10){
        alert("Total links = 10")
        return
    }

  let formattedUrl = formatAmazonURL(tabAtual);
  urlSalvas.push(formattedUrl);
  await updateStorage(urlSalvas);
  let liNew = createListItem(formattedUrl);
  document.querySelector('.list-url').append(liNew);

    // Incrementar o contador
    count++;
    updateCountUI(count);
    saveCountToStorage(count);
});


  // Adicionar botão "Limpar"
  document.getElementById("clearButton").addEventListener("click", async function () {
    // Remover todas as li
    document.querySelector('.list-url').innerHTML = "";

    // Limpar o array e o armazenamento local
    urlSalvas = [];
    await updateStorage(urlSalvas);

    // Atualizar o contador
    count = 0;
    updateCountUI(count);
    saveCountToStorage(count);
});

  function createListItem(url) {
    let liNew = document.createElement("li");
    let newText = document.createTextNode(url);
    liNew.appendChild(newText);

    let spanElement = document.createElement("span");
    let spanText = document.createTextNode("x");
    spanElement.appendChild(spanText);

    spanElement.addEventListener("click", function () {
      liNew.remove();
      // Obter o índice do URL e remover do array e do armazenamento local
      let index = urlSalvas.indexOf(url);
      if (index !== -1) {
        urlSalvas.splice(index, 1);
        updateStorage(urlSalvas);

        // Decrementar o contador
        count = Math.max(count - 1, 0);
        updateCountUI(count);
        saveCountToStorage(count);
    }
});

    liNew.appendChild(spanElement);

    return liNew;
}

async function updateStorage(urls) {
    await chrome.storage.local.set({ "cachedUrl": JSON.stringify(urls) });
}

async function copyToClipboard() {
    var urlElement = document.getElementById("currentUrl");
    var url = urlElement.innerText;

    var textarea = document.createElement("textarea");
    textarea.value = url;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand("copy");
    document.body.removeChild(textarea);

    alert("URL copiada para a área de transferência!");
}

function updateCountUI(count) {
    var countElement = document.querySelector('.count');
    countElement.innerText = count;
}

function saveCountToStorage(count) {
    chrome.storage.local.set({ "count": count });
}



function getCodeAmazon(url){
    const regex_amazon = /dp\/([^\/?]+)/;
    const correspondencia = url.match(regex_amazon);
    return correspondencia[1];
}

function formatAmazonURL(url) {
    return 'https://amazon.com.br/dp/' + getCodeAmazon(url);
}

function formatGoogleURL(url) {
    return 'https://www.googleapis.com/books/v1/volumes?q=isbn:' + getCodeAmazon(url);
}

function isAmazonURL(url) {
    // Verificar se a URL contém "amazon.com" ou "amazon.com.br"
    return /amazon\.com(\.br)?/.test(url);
}


document.getElementById("sendUrls").addEventListener("click", async function(){
    let url = await chrome.storage.local.get(["cachedUrl", "count"]);
    let urlSalvas = (url["cachedUrl"] && url["cachedUrl"].length > 0) ? url["cachedUrl"] : [];
    console.log()
    if(JSON.parse(urlSalvas).length!=10){
        alert("Menos de 10 links no botão enviar")
        return
    }
    chrome.scripting.executeScript({
        target: {
            tabId: tabs[0].id,
        },
        args: [
            urlSalvas
        ],
        func: (links) => {
            JSON.parse(links).forEach((element, key) => {
                let regex_amazon = /dp\/([^\/?]+)/;
                let correspondencia = element.match(regex_amazon);

                let urlPageElement = document.querySelector("#url_page_" + (key + 1));
                let urlPageAmazonElement = document.querySelector("#url_page_amazon_" + (key + 1));

                urlPageElement.value = element;
                urlPageAmazonElement.value = correspondencia[1];

                if (element.trim() !== '') {
                    urlPageElement.classList.add('loading-preenchido');
                }

                if (correspondencia[1].trim() !== '') {
                    urlPageAmazonElement.classList.add('loading-preenchido');
                }
            });

            let meuElemento = document.querySelector(".opacity");
            meuElemento.classList.remove("opacity");

            // Fechar a janela do popup
            chrome.runtime.sendMessage({ action: "closePopup" });
        },
    });
});

chrome.runtime.onMessage.addListener(function (request, sender, sendResponse) {
    if (request.action === "closePopup") {
        window.close();
    }
});

});
