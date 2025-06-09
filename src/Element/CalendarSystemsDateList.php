<?php

namespace Drupal\calendar_systems\Element;

use Drupal;
use Drupal\calendar_systems\CalendarSystems\CalendarSystemsDrupalDateTime;
use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Element\Datelist;
use Drupal\Core\Form\FormStateInterface;
use Exception;
use function date_default_timezone_get;

/**
 * @FormElement("datelist")
 */
class CalendarSystemsDateList extends Datelist {

  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $parts = $element['#date_part_order'];
    $increment = $element['#date_increment'];
    $date = NULL;
    if ($input !== FALSE) {
      $return = $input;
      if (empty(static::checkEmptyInputs($input, $parts))) {
        if (isset($input['ampm'])) {
          if ($input['ampm'] == 'pm' && $input['hour'] < 12) {
            $input['hour'] += 12;
          }
          elseif ($input['ampm'] == 'am' && $input['hour'] == 12) {
            $input['hour'] -= 12;
          }
          unset($input['ampm']);
        }
        $timezone = !empty($element['#date_timezone']) ? $element['#date_timezone'] : NULL;
        try {
          $date = DrupalDateTime::createFromArray($input, $timezone);
        }
        catch (Exception) {
          $form_state->setError($element, t('Selected combination of day and month is not valid.'));
        }
        if ($date instanceof DrupalDateTime && !$date->hasErrors()) {
          static::incrementRound($date, $increment);
        }
      }
    }
    else {
      $return = array_fill_keys($parts, '');
      if (!empty($element['#default_value'])) {
        $date = $element['#default_value'];
        if ($date instanceof DrupalDateTime && !$date->hasErrors()) {
          static::incrementRound($date, $increment);
          foreach ($parts as $part) {
            $format = match ($part) {
              'day' => 'j',
              'month' => 'n',
              'year' => 'Y',
              'hour' => in_array('ampm', $element['#date_part_order']) ? 'g' : 'G',
              'minute' => 'i',
              'second' => 's',
              'ampm' => 'a',
              default => '',
            };
            $return[$part] = $date->format($format);
          }
        }
      }
    }
    $timezone = $timezone ?? NULL;

    $calendar = _calendar_systems_factory($timezone, 'en');
    if ($calendar && $date && $date->format('Y') < 1600) {
      $ok = $calendar->parse($date->format('Y m d H i s'), 'Y m d H i s');
      if (!$ok) {
        $form_state->setError($element, t('Selected combination of day and month is invalid.'));
      }
      else {
        $date = DrupalDateTime::createFromTimestamp($calendar->getTimestamp(), $timezone);
      }
    }
    $return['object'] = $date;
    return $return;
  }

  public static function processDatelist(&$element, FormStateInterface $form_state, &$complete_form): array {
    // The value callback has populated the #value array.
    $date = !empty($element['#value']['object']) ? $element['#value']['object'] : NULL;

    // Set a fallback timezone.
    if ($date instanceof DrupalDateTime) {
      $element['#date_timezone'] = $date->getTimezone()->getName();
    }
    elseif (!empty($element['#timezone'])) {
      // pass
    }
    else {
      $element['#date_timezone'] = date_default_timezone_get();
    }

    $cal = _calendar_systems_factory($element['#timezone'], 'en');
    if (!$cal) {
      return parent::processDatelist($element, $form_state, $complete_form);
    }
    // $e_cal = _calendar_systems_factory($element['#timezone'], 'en', 'gregorian');
    if ($date) {
      $date = CalendarSystemsDrupalDateTime::convert($date);
    }

    // Load translated date part labels from the appropriate calendar plugin.
    $date_helper = new DateHelper();

    $element['#tree'] = TRUE;

    // Determine the order of the date elements.
    $order = !empty($element['#date_part_order']) ? $element['#date_part_order'] : [
      'year',
      'month',
      'day',
    ];
    $text_parts = !empty($element['#date_text_parts']) ? $element['#date_text_parts'] : [];

    // Output multi-selector for date.
    foreach ($order as $part) {
      switch ($part) {
        case 'day':
          $options = $date_helper->days($element['#required']);
          $format = 'j';
          $title = t('Day');
          break;

        case 'month':
          $fac = _calendar_systems_factory();
          $options = $fac->listOptions('monthNames', $element['#required']);
          $format = 'n';
          $title = t('Month');
          break;

        case 'year':
          $range = static::calendarSystemsDatetimeRangeYears($element['#date_year_range'], $date, $cal->getCalendarName());
          $min = $range[0];
          $max = $range[1];
          $cal->setTimestamp(Drupal::time()->getRequestTime());
          $rng = range(
            empty($min) ? intval($cal->format('Y') - 3) : $min,
            empty($max) ? ((int) $cal->format('Y')) + 3 : $max
          );
          $rng = array_combine($rng, $rng);
          $options = !$element['#required'] ? ['' => ''] + $rng : $rng;

          $format = 'Y';
          $title = t('Year');
          break;

        case 'hour':
          $format = in_array('ampm', $element['#date_part_order']) ? 'g' : 'G';
          $options = $date_helper->hours($format, $element['#required']);
          $title = t('Hour');
          break;

        case 'minute':
          $format = 'i';
          $options = $date_helper->minutes($format, $element['#required'], $element['#date_increment']);
          $title = t('Minute');
          break;

        case 'second':
          $format = 's';
          $options = $date_helper->seconds($format, $element['#required'], $element['#date_increment']);
          $title = t('Second', [], ['context' => 'timeperiod']);
          break;

        case 'ampm':
          $format = 'a';
          $options = $date_helper->ampm($element['#required']);
          $title = t('AM/PM');
          break;

        default:
          $format = '';
          $options = [];
          $title = '';
      }

      $default = isset($element['#value'][$part]) && trim($element['#value'][$part]) != '' ? $element['#value'][$part] : '';
      $value = $date instanceof DrupalDateTime && !$date->hasErrors() ? $date->format($format) : $default;
      if (!empty($value) && $part != 'ampm') {
        $value = intval($value);
      }

      $element['#attributes']['title'] = $title;
      $element[$part] = [
        '#type' => in_array($part, $text_parts) ? 'textfield' : 'select',
        '#title' => $title,
        '#title_display' => 'invisible',
        '#value' => $value,
        '#attributes' => $element['#attributes'],
        '#options' => $options,
        '#required' => $element['#required'],
        '#error_no_message' => FALSE,
        '#empty_option' => $title,
      ];
    }

    // Allows custom callbacks to alter the element.
    if (!empty($element['#date_date_callbacks'])) {
      foreach ($element['#date_date_callbacks'] as $callback) {
        if (function_exists($callback)) {
          $callback($element, $form_state, $date);
        }
      }
    }

    return $element;
  }

  public static function calendarSystemsDatetimeRangeYears($string, $date, $calendar_name = ''): array {
    if ($calendar_name === 'gregorian') {
      return parent::datetimeRangeYears($string, $date);
    }

    $calendar = _calendar_systems_factory(NULL, 'en', $calendar_name);
    if (!$calendar) {
      return parent::datetimeRangeYears($string, $date);
    }

    //    $datetime = new CalendarSystemsDrupalDateTime();
    $this_year = $calendar->format('Y');
    [$min_year, $max_year] = explode(':', $string);

    // Valid patterns would be -5:+5, 0:+1, 2008:2010.
    $plus_pattern = '@[\+|\-][0-9]{1,4}@';
    $year_pattern = '@^[0-9]{4}@';
    if (!preg_match($year_pattern, $min_year, $matches)) {
      if (preg_match($plus_pattern, $min_year, $matches)) {
        $min_year = ((int)$this_year) + ((int)$matches[0]);
      }
      else {
        $min_year = $this_year;
      }
    }
    else {
      try {
        $calendar->xSetDate($min_year, 1, 1);
        $min_year = $calendar->format('Y');
      }
      catch (Exception) {
        $min_year = 0;
      }
    }

    if (!preg_match($year_pattern, $max_year, $matches)) {
      if (preg_match($plus_pattern, $max_year, $matches)) {
        $max_year = ((int)$this_year) + ((int)$matches[0]);
      }
      else {
        $max_year = $this_year;
      }
    }
    else {
      try {
        $calendar->xSetDate($max_year, 1, 1);
        $max_year = $calendar->format('Y');
      }
      catch (Exception) {
        $max_year = 0;
      }
    }

    $min_year = intval($min_year);
    $max_year = intval($max_year);

    // We expect the $min year to be less than the $max year. Some custom values
    // for -99:+99 might not obey that.
    if ($min_year > $max_year) {
      $temp = $max_year;
      $max_year = $min_year;
      $min_year = $temp;
    }
    // If there is a current value, stretch the range to include it.
    if ($date instanceof DrupalDateTime) {
      $calendar->setTimestamp($date->getTimestamp());
      $value_year = $calendar->format('Y');
    }
    else {
      $value_year = '';
    }

    if (!empty($value_year)) {
      $min_year = min(intval($value_year), $min_year);
      $max_year = max(intval($value_year), $max_year);
    }
    return [$min_year, $max_year];
  }

  public static function datetimeRangeYears($string, $date = NULL) {
    return static::calendarSystemsDatetimeRangeYears($string, $date);
  }

}
