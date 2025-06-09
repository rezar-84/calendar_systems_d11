(function ($, Drupal) {
  function getLangcode() {
    console.log(drupalSettings)
    return drupalSettings && drupalSettings.path && drupalSettings.path.currentLanguage
      ? (drupalSettings.path.currentLanguage === "en" ? "en" : "fa")
      : "fa";
  }

  Drupal.behaviors.calendar_systems = {
    attach: function attach(context, settings) {
      var $context = $(context);
      $context.find('input[data-calendar-systems-calendar]').each(function () {
        var $input = $(this);

        var c = $input.attr('data-calendar-systems-calendar');
        if (c !== 'persian' && c !== 'gregorian') {
          return;
        }

        console.log(getLangcode());
        var cDef = null;
        if(c === 'persian') {
          cDef = {
            persian: {
              locale: getLangcode(),
              showHint: true,
            },
          };
        }
        else {
          cDef = {
            gregorian: {
              locale: getLangcode(),
                showHint: true,
            }
          };
        }

        var sett = {
          autoClose: true,
          format: $input.data('calendar-systems-format').replace('Y', 'YYYY').replace('m', 'MM').replace('d', 'DD'),
          position: "auto",
          onlySelectOnDate: true,
          calendarType: c,
          calendar: cDef,
          timePicker: {
            enabled: false
          },
          initialValueType: c,
          initialValue: false,
        };

        var pd = $input.pDatepicker(sett);
      });
    },
  };
})(jQuery, Drupal);

