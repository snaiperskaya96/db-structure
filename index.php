<?php
/**
 * Created by PhpStorm.
 * User: Jury Verrigni
 * Date: 14/06/16
 * Time: 14.28
 */
require_once('inc/structure.php');
$structure = new Structure();
$databases = $structure->getDatabases();
?>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="assets/css/style.css">
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.min.js"></script>
        <script src="assets/js/script.js"></script>
    </head>
    <body>
    <div class="container">
        <h3>Available Databases:</h3>
        <?php foreach($databases['available'] as $name => $db): ?>
            <div class="well">
                <div class="database-container" id="<?=$db['database'];?>">
                    <h4 class="database-name col col-md-6"><?=$name;?></h4>
                    <div class="col col-md-6 text-right" role="group" aria-label="">
                        <div class="btn btn-primary get-structure" data-id="<?=$name;?>">Get structure</div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary dropdown-toggle form-control" data-toggle="dropdown">
                                <span data-bind="label">Export To</span>&nbsp;<span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu" role="menu">
                                <li><a onclick="exportDoc('<?=$db['database'];?>')" href="#">Doc</a></li>
                            </ul>
                        </div>

                    </div>
                    <div style="clear:both;"></div>
                </div>
                <div style="clear:both"></div>
            </div>
        <?php endforeach; ?>
        <h3>Unavailable Databases:</h3>
        <div class="well">
            <?php foreach($databases['unavailable'] as $name => $error): ?>
                <p><?=$name;?>: <?=$error;?></p>
            <?php endforeach; ?>
            <div style="clear:both"></div>
        </div>
    </div>
    <div class="overlay">
        <div class="overlay-body">
        </div>
    </div>
    </body>
</html>
