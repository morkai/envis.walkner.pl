<!DOCTYPE html>
<html lang=pl>
<head>
  <meta charset=utf-8>
  <title>#ft</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: .8em;
      line-height: 1.4;
      margin: 1em;
      padding: 0;
    }
    hr {
      border: 0;
      border-top: 1px solid #000;
      clear: both;
    }
    p {
      font-size: .8em;
      margin: 0;
    }
    #address, #contact {
      float: left;
    }
    #contact {
      margin-left: 2em;
    }
    .property {
      clear: both;
    }
    .name {
      float: left;
      width: 7em;
    }
    #address .name {
      width: 4.5em;
    }
    .value {
      float: left;
    }
    #page {
      text-align: right;
      vertical-align: bottom;
      height: 4em;
    }
  </style>
  <script>
    window.onload = function() {
      var values = {};

      document.location.search.substring(1).split('&').forEach(function(part) {
        part = part.split('=', 2);
        values[part[0]] = unescape(part[1]);
      });
      
      ['topage', 'page'].forEach(function(property) {
        if (!(property in values)) {
          return;
        }
        
        var value    = values[property],
            elements = document.getElementsByClassName(property);

        for (var i in elements) {
          elements[i].innerHTML = value;
        }
      });
    }
  </script>
</head>
<body>
  <hr>
  <div id="address">
    <p>
      Walkner elektronika przemysłowa Zbigniew Walukiewicz<br>
      Nowa Wieś Kętrzyńska 7, 11-400 Kętrzyn, POLSKA
    </p>
    <div class="property">
      <p class="name">NIP:</p>
      <p class="value">742-100-54-87</p>
    </div>
    <div class="property">
      <p class="name">REGON:</p>
      <p class="value">510329685</p>
    </div>
  </div>
  <div id="contact">
    <div class="property">
      <p class="name">Telefon stac.:</p>
      <p class="value">(89) 752 27 78</p>
    </div>
    <div class="property">
      <p class="name">Telefon kom.:</p>
      <p class="value">603 930 725</p>
    </div>
    <div class="property">
      <p class="name">Adres e-mail:</p>
      <p class="value">walkner@walkner.pl</p>
    </div>
    <div class="property">
      <p class="name">Strona WWW:</p>
      <p class="value">http://walkner.pl/</p>
    </div>
  </div>
  <div id="page">
    <p>Strona <span class="page">0</span> z <span class="topage">0</span></p>
  </div>
</body>
</html>