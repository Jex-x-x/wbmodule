class Wbs24WbapiAdmin {
  activateOptionsForCurrentValue(field, currentValue) {
    let options = document.querySelectorAll(`select[name=${field}] option[data-filter]`);
    for (let elem of options) {
      this.activateOption(elem, currentValue);
    }
  }

  activateOption(elem, currentValue) {
    let multipleFilter = elem.dataset.filter;
    let show = false;

    const filterValues = multipleFilter.split(',');
    for (let filter of filterValues) {
      if (filter == currentValue || filter == "all") {
        show = true;
        break;
      }
    }

    if (show) {
      elem.hidden = false;
      if (elem.dataset.selected == 'Y') elem.selected = true;
    } else {
      elem.hidden = true;
      elem.selected = false;
    }
  }

  areAllSelectsSet(requiredSelectNames) {
    let nothingIsExist = false;
    for (let field of requiredSelectNames) {
      let select = document.querySelector(`select[name=${field}]`);
      if (select.value == 'nothing') {
        nothingIsExist = true;
        break;
      }
    }

    return !nothingIsExist;
  }

  getAccountPrefixByIndex(index) {
    let prefix = '';
    if (index > 1) {
      prefix = 'a' + index + '_';
    }

    return prefix;
  }
}
