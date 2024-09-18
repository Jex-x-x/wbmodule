class Wbs24WbapiStringTemplate {
  setInputHandlers(menuButtonId, inputId, marks) {
    if (!BX) return

    BX.ready(() => {
      BX.bind(BX(menuButtonId), 'click', (event) => {
        event.preventDefault()
        BX.adminShowMenu(event.target, this.getAdminMenuStruct(inputId, marks), '')
      })
    })
  }

  getAdminMenuStruct(inputId, marks) {
    let menuStruct = []
    for (let item of marks) {
      if (typeof item['MENU'] != 'undefined') {
        menuStruct.push({
          'TEXT': item['TEXT'],
          'MENU': this.getAdminMenuStruct(inputId, item['MENU'])
        })
      } else if (typeof item['MARK'] != 'undefined') {
        menuStruct.push({
          'TEXT': item['TEXT'],
          'ONCLICK': 'StringTemplate.addMarkToCursorPosition("'+inputId+'", "{'+item['MARK']+'}")',
        })
      }
    }

    return menuStruct
  }

  addMarkToCursorPosition(inputId, mark) {
    let input = document.querySelector('#'+inputId)
    if (input) {
      let start = input.selectionStart
      let end = input.selectionEnd
      input.value = input.value.substring(0, start) + mark + input.value.substring(end)
      input.focus()
      input.selectionEnd = (start == end) ? (end + mark.length) : end
    }
  }

  async request(action, siteId) {
    let url =
      '/bitrix/tools/wbs24.wbapi/interfaceAjax.php?ACTION=' + action
      + '&siteId' + siteId
    ;
    let response = await fetch(url);
    let result = await response.json();

    return  JSON.parse(result);
  }
}

if (typeof window === 'undefined') module.exports = Wbs24WbapiStringTemplate;
