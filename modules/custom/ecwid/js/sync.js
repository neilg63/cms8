(function ($) {

  'use strict';
  Drupal.behaviors.ecwid = {
    attach: function (context) {
      if (context == document) {
        if (!drupalSettings.ecwid) {
           drupalSettings.ecwid = {};
        }
        var sync = function() {
          var s = drupalSettings.ecwid;
          if (!s.syncStarted) {
            var products = $('.ec-store .grid-product');
            if (products.length > 0) {
              s.syncStarted = true;
              var i = 0, num = products.length, items = [], item = {},
                it, cls, prId, el;
              for (; i < num; i++) {
                it = products.eq(i);
                cls = it.attr('class');
                prId = cls.split('product--id-').pop().split(' ').shift();
                if ($.isNumeric(prId)) {
                  item = {
                    id: prId,
                    title: '',
                    price: 0.00
                  }
                  el = it.find('.grid-product__price-amount');
                  if (el.length>0) {
                    item.price = el.text().replace(/[^0-9.,]/,'');
                    if ($.isNumeric(item.price)) {
                      item.price = item.price.replace(/,(\d\d)$/,".$1").replace(/,/g,'');
                      item.price = parseFloat(item.price);
                    } else {
                      item.price = 0.00;
                    }
                  }
                  el = it.find('.grid-product__title-inner');
                  if (el.length>0) {
                    item.title = $.trim(el.text());
                    items.push(item);
                  }
                }
              }
              if (items.length > 0) {
                var data = {
                  items: items,
                  numItems: items.length
                };
                $.ajax({
                  url: '/admin/content/ecwid/save',
                  method: "POST",
                  data: data,
                  success: function(data) {
                    console.log(data);
                    if (data.saved) {
                      var txt = data.items.length + ' ECWID products saved'; 
                      $('#saved-message').html(txt);
                    }
                  }
                });
              }
              clearInterval(s.interval);
            }
          }
        }
        drupalSettings.ecwid.syncStarted = false;
        drupalSettings.ecwid.interval = setInterval(sync, 5000); 
      }
    }
  };

})(jQuery);