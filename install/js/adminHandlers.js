class Wbs24WbapiAdminHandlers {
  admin = null;

  constructor(objects = []) {
    this.admin = objects['Wbs24WbapiAdmin'] || new Wbs24WbapiAdmin();
  }

  start(accounts) {
    document.addEventListener("DOMContentLoaded", () => {
      for (let index of accounts) {
        let account = this.admin.getAccountPrefixByIndex(index);

        this.addNumberTemplateHandlers(account);
        this.addCustomersHandler(account);
      }
    });
  }

  addNumberTemplateHandlers(account) {
    const orderNumbersTemplates = document.querySelectorAll('.'+ account +'js-order-number-template');
    const input = document.querySelector('input[name="'+ account +'orderNumberTemplate"]');

    if (orderNumbersTemplates && input) {
      this.addHandlers(orderNumbersTemplates, input);
    }
  }

  addHandlers(orderNumbersTemplates, input) {
    orderNumbersTemplates.forEach(orderNumberTemplate => {
      this.addHandler(orderNumberTemplate, input);
    });
  }

  addHandler(orderNumberTemplate, input) {
    orderNumberTemplate.addEventListener('click', (event) => {
      event.preventDefault();
      input.value += orderNumberTemplate.dataset.template;
    });
  }

  addCustomersHandler(account) {
    let userId = document.querySelector('[name="'+account+'userId"]');
    let customerSelect = document.querySelector('[name="'+account+'customerId"]');

    if (userId && userId.value !== 'undefined' && customerSelect) {
      userId.addEventListener('change', () => {
        this.changeCustomerOptions(userId.value, customerSelect);
      });
    }
  }

  async changeCustomerOptions(userId, select) {
    let url =
      '/bitrix/tools/wbs24.wbapi/options.php?ACTION=loadCustomerIds&user_id=' + userId;
    let response = await fetch(url);
    let responseText = await response.text();

    let data = JSON.parse(responseText);
    if (response.status == 200) {
      let customerSelect = select;
      customerSelect.innerHTML = "";

      for (var key in data) {
        let newOption = new Option(data[key], key);
        customerSelect.append(newOption);
      }
    }
  }
}
