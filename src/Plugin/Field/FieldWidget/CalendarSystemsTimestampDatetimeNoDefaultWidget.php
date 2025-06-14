<?php

namespace Drupal\calendar_systems\Plugin\Field\FieldWidget;

use Drupal;
use Drupal\calendar_systems\Element\CalendarSystemsDateTime;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Element\Datetime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Datetime\Plugin\Field\FieldWidget\TimestampDatetimeWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use function date_default_timezone_get;

/**
 * Replaces core's widget with a localizable one.
 *
 * @FieldWidget(
 *   id = "datetime_timestamp_no_default",
 *   label = @Translation("CalendarSystems Datetime Timestamp for Scheduler"),
 *   description = @Translation("An optional datetime field. Does not provide a
 *   default time if left blank. Defined by Scheduler module."), field_types =
 *   {
 *     "timestamp"
 *   }
 * )
 */
class CalendarSystemsTimestampDatetimeNoDefaultWidget extends TimestampDatetimeWidget {

  /**
   * Callback function to add default time to the input date if needed.
   *
   * This will intercept the user input before form validation is processed.
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE) {
      $date_input = $element['#date_date_element'] != 'none' && !empty($input['date']) ? $input['date'] : '';
      $time_input = $element['#date_time_element'] != 'none' && !empty($input['time']) ? $input['time'] : '';
      // If there is an input date but no time and the date-only option is on
      // then set the input time to the default specified by scheduler options.
      $config = Drupal::config('scheduler.settings');
      if (!empty($date_input) && empty($time_input) && $config->get('allow_date_only')) {
        $input['time'] = $config->get('default_time');
      }
    }
    // Chain on to the standard valueCallback for Datetime as we do not want to
    // duplicate that core code here.
    return CalendarSystemsDateTime::valueCallback($element, $input, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    // Remove 'Leave blank to use the time of form submission' which is in the
    // #description inherited from TimestampDatetimeWidget. The text here is not
    // used because it is entirely replaced in scheduler_form_node_form_alter()
    // However the widget is generic and may be used elsewhere in the future.
    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();
    // $element['value']['#description'] = $this->t('Format: %format. Leave blank for no date.', ['%format' => Datetime::formatExample($date_format . ' ' . $time_format)]);
    $pattern = $date_format . ' ' . $time_format;
    $element['value']['#description'] = $this->t('Format: %format. Leave blank for no date.', [
     '%format' => \Drupal::service('date.formatter')->getPattern('custom', $pattern)
    ]);
    // Set the callback function to allow interception of the submitted user
    // input and add the default time if needed. It is too late to try this in
    // function massageFormValues as the validation has already been done.
    $element['value']['#value_callback'] = [$this, 'valueCallback'];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      // @todo The structure is different whether access is denied or not, to
      //   be fixed in https://www.drupal.org/node/2326533.
      $date = NULL;
      $timezone = date_default_timezone_get();
      if (isset($item['value']) && $item['value'] instanceof DrupalDateTime) {
        $date = $item['value'];
        $calendar = _calendar_systems_factory($timezone, 'en');
        if ($calendar && $date->format('Y') < 1600) {
          $ok = $calendar->parse($date->format('Y m d H i s'), 'Y m d H i s');
          if ($ok) {
            $date = DrupalDateTime::createFromTimestamp($calendar->getTimestamp(), $timezone);
          }
        }
      }
      elseif (isset($item['value']['object']) && $item['value']['object'] instanceof DrupalDateTime) {
        $date = $item['value']['object'];
      }
      $item['value'] = $date ? $date->getTimestamp() : NULL;
    }
    return $values;
  }

}
