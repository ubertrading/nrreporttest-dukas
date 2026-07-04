var instruments = [];

$.ajax({
  url: 'instruments.json',
  async: false,
  dataType: 'json',
  success: function (response) {
    instruments = response;
  }
});

function generateSymbols() {
  var symbols = '<option disabled selected></option>';
  for (var sym in instruments) {
    symbols += '<option value="' + sym + '">' + sym + '</option>';
  }
  return symbols;
}

function addSymbols(name) {
  var currencySelect = document.getElementById(name);
  currencySelect.innerHTML = generateSymbols();
}