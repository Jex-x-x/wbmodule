class Wbs24WbapiOrdersTab {
  debug = false;
  orderErrorClass = 'js-wbs24-order-error';

  triggerGettingDomElements(orderId, accountIndex) {
    this.orderId = orderId;
    this.accountIndex = accountIndex;

    // collect
    this.collectBtn = document.querySelector(".wbs24_collect-btn");
    this.collectOrderMessage = document.querySelector(".wbs24_collect_td");

    // act
    this.actCreate = document.querySelector(".wbs24_act-create");
    this.createActMessage = document.querySelector(".wbs24_act_td");
    this.actErrorMessage = document.querySelector(".js-wbs24-act-error");
    this.actStatusMessage = document.querySelector(".js-wbs24-act-status");

    // package label
    this.packageLabel = document.querySelector(".wbs24_package-label");
    this.packageLabelMessage = document.querySelector(".wbs24_package_td");
    this.packageLabelStatusMessage = document.querySelector(".js-wbs24-package-label-status");

    // cancel
    this.cancelBtn = document.querySelector(".js-order-cancel");
    this.cancelErrorMessage = document.querySelector(".js-order-cancel-error-label");
    this.cancelStatusMessage = document.querySelector(".js-order-cancel-label");
  }

  setLocalstorageData() {
    let allSelects = this.allSelects;
    let onePackage = this.onePackage;
    let orderId = this.orderId;

    for (let j = 0; j < allSelects.length; j++) {
      allSelects[j].onchange = function(){
        localStorage[orderId+"item_"+j] = this.value;
      };
    }
    onePackage.addEventListener("change", function (event) {
        event.target.checked
        ? localStorage[orderId + "one_package"] = "On"
        : localStorage[orderId + "one_package"] = "Off";
    });
  }

  trackButtonPressCollectOrder(postingNumber) {
    let collectBtn = this.collectBtn;
    let orderId = this.orderId;
    let collectOrderMessage = this.collectOrderMessage;
    let packageLabel = this.packageLabel;
    let actCreate = this.actCreate;

    if (collectBtn) {
      collectBtn.onclick = async (e) => {
        e.preventDefault();
        this.hideOrderErrors();
        let packages = {};
        let isOnePackage = 'on';
        let jsonPackages = JSON.stringify(packages);
        let url =
          '/bitrix/tools/wbs24.wbapi/ajax.php?ACTION=collect_order&packages=' + jsonPackages
          + '&one_package=' + isOnePackage
          + '&order_id=' + orderId
          + '&posting_number=' + postingNumber
          + '&account_index=' + this.accountIndex
        ;
        e.preventDefault();
        let response = await fetch(url);
        let responseText = await response.text();
        let data = JSON.parse(responseText);
        if (this.debug) console.log(data);
        if (data.result == "success") {
          collectBtn.setAttribute('disabled', true);
          collectOrderMessage.style.display = 'inline-block';
          packageLabel.removeAttribute('disabled');
          actCreate.removeAttribute('disabled');
        } else {
          let orderError = document.querySelector("."+this.orderErrorClass+"[data-error='"+data.result+"']");
          if (!orderError) orderError = document.querySelector("."+this.orderErrorClass+"[data-error='UNKNOWN']");
          if (orderError) {
            orderError.style.display = 'inline-block';
          }
        }
      };
    }
  }

  hideOrderErrors() {
    let orderErrors = document.querySelectorAll("."+this.orderErrorClass);
    for (let error of orderErrors) {
      error.style.display = 'none';
    }
  }

  trackButtonPressActCreate(deliveryMethodId, postingNumber) {
    let actCreateBtn = this.actCreate;
    let actStatusMessage = this.actStatusMessage;
    let url =
      '/bitrix/tools/wbs24.wbapi/ajax.php?ACTION=create_act&delivery_method_id=' + deliveryMethodId
      + '&posting_number=' + postingNumber
      + '&account_index=' + this.accountIndex
      + '&disabled=' + true
    ;

    if (actCreateBtn) {
      actCreateBtn.onclick = async (e) => {
        actCreateBtn.setAttribute('disabled', true);
        e.preventDefault();
        let response = await fetch(url);
        let responseText = await response.text();
        let data = JSON.parse(responseText);
        if (this.debug) console.log(data);
        if (data.result == "success") {
          this.actErrorMessage.style.display = 'none';
          actStatusMessage.style.display = 'inline-block';
          this.setActLink(data.create_act_id, postingNumber);
        } else {
          this.actErrorMessage.style.display = 'inline-block';
        }
      };
    }
  }

  setActLink(actId, postingNumber) {
    let timerId = setInterval(async () => {
      await this.checkActStatus(actId, postingNumber, timerId);
    }, 10000);
  }

  async checkActStatus(actCreateId, postingNumber, timerId) {
    let createActMessage = this.createActMessage;
    let actStatusMessage = this.actStatusMessage;

    if (actCreateId) {
      let url =
        '/bitrix/tools/wbs24.wbapi/ajax.php?ACTION=check_act_status&posting_number=' + postingNumber
        + '&account_index=' + this.accountIndex
        + '&act_сreate_id=' + actCreateId
      ;
      let response = await fetch(url);
      let responseText = await response.text();
      let data = JSON.parse(responseText);
      if (this.debug) console.log(data);
      if (data.result == "success") {
        if (data.link_to_document) {
          clearInterval(timerId);
          actStatusMessage.style.display = 'none';
          createActMessage.style.display = 'inline-block';
          createActMessage.href = data.link_to_document;
        }
      }
    }
  }

  trackButtonPressPackageLabel(postingNumber) {
    let packageLabelBtn = this.packageLabel;
    if (packageLabelBtn) {
      packageLabelBtn.onclick = async (e) => {
        packageLabelBtn.setAttribute('disabled', true);
        e.preventDefault();

        this.checkPackageLabelStatus(postingNumber);
      };
    }
  }

  setPackageLabelLink(postingNumber) {
    setTimeout(async () => {
      await this.checkPackageLabelStatus(postingNumber);
    }, 30000);
  }

  async checkPackageLabelStatus(postingNumber) {
    let packageLabelMessage = this.packageLabelMessage;
    let packageLabelStatusMessage = this.packageLabelStatusMessage;
    let url =
      '/bitrix/tools/wbs24.wbapi/ajax.php?ACTION=package_label&posting_number=' + postingNumber
      + '&account_index=' + this.accountIndex
      + '&disabled=' + true
    ;

    let response = await fetch(url);
    let responseText = await response.text();
    let data = JSON.parse(responseText);
    if (this.debug) console.log(data);
    if (data.result == "success") {
      if (data.link_to_document) {
        packageLabelStatusMessage.style.display = 'none';
        packageLabelMessage.href = data.link_to_document;
        packageLabelMessage.style.display = 'inline-block';
      }
    } else {
      this.setPackageLabelLink(postingNumber);
      packageLabelStatusMessage.style.display = 'inline-block';
    }
  }

  trackButtonPressCancelOrder(postingNumber) {
    let cancelBtn = this.cancelBtn;
    let cancelErrorMessage = this.cancelErrorMessage;
    let cancelStatusMessage = this.cancelStatusMessage;
    let url =
      '/bitrix/tools/wbs24.wbapi/ajax.php?ACTION=cancel&posting_number=' + postingNumber
      + '&account_index=' + this.accountIndex
    ;

    cancelBtn.onclick = async (e) => {
      e.preventDefault();
      if (confirm(cancelBtn.dataset.confirmMessage)) {
        let response = await fetch(url);
        let responseText = await response.text();
        let data = JSON.parse(responseText);
        if (this.debug) console.log(data);
        if (data.result == "success") {
          cancelBtn.setAttribute('disabled', true);
          cancelStatusMessage.style.display = 'inline-block';
          cancelErrorMessage.style.display = 'none';

          let collectTrs = document.querySelectorAll(".js-order-collect-tr");
          for (let tr of collectTrs) {
            tr.style.display = 'none';
          }
        } else {
          cancelErrorMessage.style.display = 'inline-block';
          cancelStatusMessage.style.display = 'none';
        }
      }
    };
  }
}
