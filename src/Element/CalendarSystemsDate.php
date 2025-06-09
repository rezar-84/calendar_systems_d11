<?php

namespace Drupal\calendar_systems\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Date;

/**
 * @FormElement("date")
 */
class CalendarSystemsDate extends Date {

  public static function preRenderDate($element) {
    $element = parent::preRenderDate($element);
    if ($element['#attributes']['type'] === 'date') {
      $element['#attributes']['type'] = 'text';
    }
    return $element;
  }

  public static function processDate(&$element, FormStateInterface $form_state, &$complete_form): array {
    $type = $element['#attributes']['type'];
    if (($type === 'date' || $type === 'text') && !empty($element['#date_date_format'])) {
      // Element jumps back to gregorian on form submit with a form with errors.
      if (!empty($element['#value'])) {
        $parents = $element['#parents'];
        array_pop($parents);
        $fs = $form_state->getValue($parents);
        $element['#value'] = _calendar_systems_tmp_fix_string($fs['date']);
      }

      // Attach js date picker.
      $lib = [];
      foreach ($element['#attached']['library'] as $item) {
        if ($item !== 'core/drupal.date' && $item !== 'calendar_systems/picker') {
          $lib[] = $item;
        }
      }
      $lib[] = 'calendar_systems/picker';
      $element['#attached']['library'] = $lib;
      $cs = _calendar_systems_factory();
      if ($cs) {
        $element['#attached']['drupalSettings']['calendar_systems'] = [
          'calendar' => $cs->getCalendarName(),
          'lang' => $cs->getLangcode(),
        ];
      }

      // Js date picker works on text fields, date field interferes with us.
      $element['#attributes']['type'] = 'text';
    }
    return $element;
  }

  public function getInfo() {
    return ['#date_date_element' => 'text'] + parent::getInfo();
  }

}
