<?php
/**
 * Created by PhpStorm.
 * User: NLaptop
 * Date: 02/09/2015
 * Time: 15:13
 */

include("SpotifyArtistsTransfer.php");

$sat = new SpotifyArtistsTransfer("tokenSender","tokenReceiver");
echo $sat;