class Wbs24WbapiHandlers {
  admin = null;

  constructor(objects = []) {
    this.admin = objects['Wbs24WbapiAdmin'] || new Wbs24WbapiAdmin();
  }

  addHandlersForAccounts(accounts) {
    document.addEventListener("DOMContentLoaded", () => {
      for (let index of accounts) {
        let account = this.admin.getAccountPrefixByIndex(index);

        this.addRfbsHandler(account);
        this.addWarningMessageHandler(account);
        this.addStockPropertiesHandlers(account, index);
      }
    });
  }

  addRfbsHandler(account) {
    let name = account + 'rfbs';
    let checkbox = document.querySelector(`input[name=${name}]`);
    let trackNumber = document.querySelector(`.wbs24_wbapi_option_${account}track_number`);

    if (checkbox && trackNumber) {
      if (checkbox.checked) trackNumber.style.display = "table-row";

      checkbox.addEventListener('change', (event) => {
          if (event.currentTarget.checked) {
              trackNumber.style.display = "table-row";
          } else {
              trackNumber.style.display = "none";
          }
      });
    }
  }

  addWarningMessageHandler(account) {
    const requiredSelectNames = [
      account + 'siteId',
      account + 'deliveryServiceId',
      account + 'paymentSystemId',
      account + 'personTypeId',
      account + 'propertyOfExternalOrderNumber',
      //account + 'propertyOfShipmentDate',
    ];

    for (let name of requiredSelectNames) {
      let select = document.querySelector(`select[name=${name}]`);

      if (select) {
        select.addEventListener('change', () => {
          const allSelectsAreSet = this.admin.areAllSelectsSet(requiredSelectNames);
          const note = document.querySelector(`.wbs24_wbapi_option_${account}site_note`);

          if (note) note.style.display = allSelectsAreSet ? "none" : "table-row";
        });
      }
    }
  }

  addStockPropertiesHandlers(account, index) {
    let stockType = document.querySelector(`select[name=${account}stockType]`);
    let stockOptions = document.querySelectorAll(`.${account}wbs24-wbapi-stock-properties`);

    if (stockType && stockOptions) {

      if (stockType.value == 'stocks_from_property') {
        stockOptions.forEach(stockOption => {
          let stockOptionTr = stockOption.closest('tr');
          stockOptionTr.style.display = 'table-row';
        });
      } else {
        stockOptions.forEach(stockOption => {
          let stockOptionTr = stockOption.closest('tr');
          stockOptionTr.style.display = 'none';
        })
      }

      stockType.addEventListener('change', (event) => {
        if (event.target.value == 'stocks_from_property') {
          stockOptions.forEach(stockOption => {
            let stockOptionTr = stockOption.closest('tr');
            stockOptionTr.style.display = 'table-row';
          });
        } else {
          stockOptions.forEach(stockOption => {
            let stockOptionTr = stockOption.closest('tr');
            stockOptionTr.style.display = 'none';
          })
        }
      });
    }
  }
}
