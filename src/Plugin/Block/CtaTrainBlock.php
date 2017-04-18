<?php
/**
 * Created by PhpStorm.
 * User: oz
 * Date: 3/29/17
 * Time: 1:39 PM
 */

/**
 * @file
 * Contains \Drupal\cta_train\Plugin\Block\ozBlock.
 */
namespace Drupal\cta_train\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Http;
use Drupal\Component\Serialization\Json;


/**
 * Provides a 'cta_train' block.
 *
 * @Block(
 *   id = "cta_train_block",
 *   admin_label = @Translation("CTA Train Block"),
 *   category = @Translation("Custom CTA Train Block")
 * )
 */

class CtaTrainBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */

  public function build() {

    $output = _cta_train_pull();

    // Builds a renderable array to display the content of _cta_train_pull()
    // using the cta-train.html.twig template as the content of the block.
    return array(
      // Define the theme hook to call
      '#theme' => 'cta_train',
      // Enter the variables defined by the hook (see hook_theme())
      '#ohare_north' => $output['ohare_north'],
      '#ohare_south' => $output['ohare_south'],
      // Define cache tags for the render array
      '#cache' => array(
         'max-age' => 0,
      )
    );

  }
}

/**
 * The _cta_train_pull() obtains, and parses the JSON received from the
 * chicago transit authority (CTA) train API.
 *
 * Available values:
 * staId = Station ID ex. "40670"
 * stpId = Stop ID ex. "30130"
 * staNm = Textual proper name of parent station ex. "Western (O'Hare branch)"
 * stpDe = Textual description of platform for which this prediction applies ex. "Service toward Forest Park"
 * rn = Run number of train being predicted for ex. "201"
 * rt = Textual, abbreviated route name of train being predicted for ex. "Blue"
 * destSt = GTFS unique stop ID where this train is expected to ultimately end its service run ex. "30077"
 * destNm = Friendly destination description ex. "Forest Park"
 * trDr = Numeric train route direction code (see cta train API appendixes) ex. "5"
 * prdt = Date-time format stamp for when the prediction was generated ex. "2017-04-04T11:07:46"
 * arrT = Date-time format stamp for when a train is expected to arrive/depart ex. "2017-04-04T11:11:46"
 * isApp = Indicates that Train Tracker is now declaring “Approaching” or “Due” on site for this train ex. "0"
 * isSch = Boolean flag to indicate whether this is a live prediction
 * or based on schedule in lieu of live data ex. "0"
 * isDly = Boolean flag to indicate whether a train is considered “delayed” in Train Tracker ex. "0"
 * isFlt = Boolean flag to indicate whether a potential fault has been detected ex. "0"
 * flags = Train flags (not presently in use) ex. null
 * lat = Latitude position of the train in decimal degrees ex. "41.92751"
 * lon = Longitude position of the train in decimal degrees ex. "-87.70545"
 * heading = Heading, expressed in standard bearing degrees
 * (0 = North, 90 = East, 180 = South, and 270 = West; range is 0 to 359, progressing clockwise) ex. "132"
 *
 */

function _cta_train_pull() {

  // stores the url with api key, station number and output type
  $ohare = 'http://lapi.transitchicago.com/api/1.0/ttarrivals.aspx?key=[YOUR_CTA_API_KEY]&mapid=40670&outputType=JSON';

  // Use Gulp to obtain the data from the url.
  $client = \Drupal::httpClient();
  $ohare_request = $client->request('GET', $ohare)->getBody();
  // Decode the JSON into an array to work with.
  $ohare_data = Json::decode($ohare_request);
  $ohare_trains = $ohare_data['ctatt']['eta'];

  foreach ($ohare_trains as $train) {
    // Loop through the trains and set the values for the first northbound
    // and southbound train. Once both trains are set, no new value is set.
    //
    if (!isset($variables['ohare_north']) || !isset($variables['ohare_south'])) {
      switch ($train['destNm']) {
        case 'O\'Hare':
          if (!isset($variables['ohare_north'])) {
            $variables['ohare_north']['time'] = (strtotime($train['arrT']) - strtotime($train['prdt'])) / 60 . ' Mins';
            $variables['ohare_north']['destination'] = $train['destNm'];
            $variables['ohare_north']['train_number'] = 'Blue Line #' . $train['rn'];
          }
          break;

        case 'Forest Park':
          if (!isset($variables['ohare_south'])) {
            $variables['ohare_south']['time'] = (strtotime($train['arrT']) - strtotime($train['prdt'])) / 60 . ' Mins';
            $variables['ohare_south']['destination'] = $train['destNm'];
            $variables['ohare_south']['train_number'] = 'Blue Line #' . $train['rn'];
          }
          break;
      }
    }
  }
  return $variables;
}

