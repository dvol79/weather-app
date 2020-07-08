<div class="container">
    <div class="header clearfix">
        <h3 class="text-muted">
            Прогноз погоды на 5 дней. Город: <strong class="text-uppercase"><?php echo $config['owm_sity'] ?></strong>
        </h3>
    </div>
    <?php if (isset($_SESSION['message'])) { ?>
        <div class="alert alert-info text-center" style="margin-top:20px;">
            <?php echo $_SESSION['message']; ?>
        </div>
        <?php unset($_SESSION['message']); } ?>

    <div class="row">
        <div class="col-sm-12 jumbotron">
            <?php if ($data) : ?>

                <!-- Nav tabs -->
                <ul class="nav nav-tabs nav-justified" role="tablist">
                    <?php foreach ($data as $key => $day) : ?>
                        <li role="presentation" class="<?php if ($key == 0) echo 'active'; else echo ''; ?>">
                            <a href="#<?php echo $day["date"]; ?>" aria-controls="<?php echo $day["date"]; ?>" role="tab" data-toggle="tab">
                                <strong><?php echo $app->getDayDate($day['date']); ?></strong>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Tab panes -->
                <div class="tab-content">
                    <?php foreach ($data as $key => $day) : ?>
                        <div role="tabpanel" class="tab-pane fade in <?php if ($key == 0) echo 'active'; else echo ''; ?>" id="<?php echo $day["date"]; ?>">
                            <div class="media">
                                <div class="media-left media-top">
                                    <a href="#">
                                        <img alt="" class="media-object" src="assets/img/w_icons/<?php echo $day["icon"]; ?>.png">
                                    </a>
                                </div>
                                <div class="media-body">
                                    <h4 class="media-heading text-center"><em>Текущие данные:</em></h4>
                                    <p class="p_app"><strong>Температура:</strong> <?php echo $app->getTempSign($day['temp_curr']); ?> &#8451</p>
                                    <p class="p_app"><strong>Влажность:</strong> <?php echo intval($day["humidity"]); ?> %</p>
                                    <p class="p_app"><strong>Скорость ветра:</strong> <?php echo intval($day["wind_spid"]); ?> м/с.</p>
                                    <p class="p_app"><strong>Направление ветра:</strong> <?php echo $day["wind_dirn"]; ?></p>
                                    <p class="p_app"><strong>Характеристика:</strong> <?php echo $day["description"]; ?></p>
                                    <a href="#<?php echo $day['id']; ?>" data-toggle="modal" data-target="#<?php echo $day['id']; ?>">Изменить данные</a>
                                    <hr>
                                    <h4 class="media-heading text-center"><em>Средние данные за 14 дней:</em></h4>
                                    <?php $avg_data = $app->getAvgValuesDay($day["date"]); ?>
                                    <p class="p_app"><strong>Температура:</strong> <?php echo $app->getTempSign($avg_data['temp']); ?> &#8451</p>
                                    <p class="p_app"><strong>Влажность:</strong> <?php echo intval($avg_data["humdt"]); ?> %</p>
                                    <p class="p_app"><strong>Скорость ветра:</strong> <?php echo intval($avg_data["winds"]); ?> м/с.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Modal form for update weather data [id] -->
                        <div class="modal fade" id="<?php echo $day['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="mLabel<?php echo $day['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog">

                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                        <h4 class="modal-title" id="mLabel<?php echo $day['id']; ?>">Изменить данные погоды на: <?php echo $day["date"]; ?></h4>
                                    </div>

                                    <div class="modal-body">
                                        <div class="container-fluid">
                                            <form class="form-horizontal" name="update" action="index.php" method="post">
                                                <div class="form-group fg-app">
                                                    <label for="temp_curr" class="col-sm-5 control-label">Температура:</label>
                                                    <div class="col-sm-7">
                                                        <input type="text" name="temp_curr" class="form-control" id="temp_curr" value="<?php echo $day['temp_curr']; ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group fg-app">
                                                    <label for="humidity" class="col-sm-5 control-label">Влажность:</label>
                                                    <div class="col-sm-7">
                                                        <input type="text" name="humidity" class="form-control" id="humidity" value="<?php echo $day["humidity"]; ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group fg-app">
                                                    <label for="wind_spid" class="col-sm-5 control-label">Скорость ветра:</label>
                                                    <div class="col-sm-7">
                                                        <input type="text" name="wind_spid" class="form-control" id="wind_spid" value="<?php echo $day["wind_spid"]; ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group fg-app">
                                                    <label for="wind_dirn" class="col-sm-5 control-label">Направление ветра:</label>
                                                    <div class="col-sm-7">
                                                        <input type="text" name="wind_dirn" class="form-control" id="wind_dirn" value="<?php echo $day["wind_dirn"]; ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group fg-app">
                                                    <label for="description" class="col-sm-5 control-label">Описание:</label>
                                                    <div class="col-sm-7">
                                                        <input type="text" name="description" class="form-control" id="description" value="<?php echo $day["description"]; ?>">
                                                    </div>
                                                </div>
                                                <input type="hidden" name="date" value="<?php echo $day["date"]; ?>">
                                                <button type="submit" class="btn btn-default pull-right">Сохранить</button>
                                                <div class="clearfix"></div>
                                            </form>
                                        </div><!-- .container-fluid -->
                                    </div><!-- .modal-body -->

                                </div><!-- .modal-content -->
                            </div><!-- .modal-dialog -->
                        </div><!-- .modal -->

                    <?php endforeach; ?>
                </div><!-- .tab-content -->

            <?php endif; ?>
        </div><!-- .jumbotron -->
    </div><!-- .row -->
</div><!-- .container -->