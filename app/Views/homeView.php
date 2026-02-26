<?php

use App\Helpers\ViewHelper;
//TODO: set the page title dynamically based on the view being rendered in the controller.
$page_title = 'Home';
ViewHelper::loadHeader($page_title);
?>



<?php

ViewHelper::loadJsScripts();
ViewHelper::loadFooter();
?>
