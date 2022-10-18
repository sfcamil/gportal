<?php

namespace Drupal\gepsis\Path;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor to redirect taxonomy paths to a view with contextual arg.
 */
class AlterPathProcessor implements OutboundPathProcessorInterface
{

    /**
     * {@inheritdoc}
     *
     * https://drupal.stackexchange.com/questions/299897/change-the-path-used-by-a-route-dynamically
     * https://drupal.stackexchange.com/questions/213855/how-do-i-programmatically-alter-and-rewrite-the-current-path
     *
     * https://drupal.stackexchange.com/questions/268798/add-a-condition-to-show-menu-link-in-yml
     */
    public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
        return;
        if ($path == '/form/declaration-annuelle') {

            $path = '/form/declaration-annuelle-ostra';

        }
        return $path;
    }

}