<?php
/*******************************************************************************
 *
 *  filename    : peopledashboard.php
 *  last change : 2019-07-01
 *  website     : http://www.ecclesiacrm.com
 *  copyright   : 2019 Philippe Logel all right reserved not MIT licence
 *                This code can't be incoprorated in another software without authorization
 *
 ******************************************************************************/

use EcclesiaCRM\SessionUser;
use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\Utils\MiscUtils;

require $sRootDocument . '/Include/Header.php';
?>

<!-- Default box -->
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= _('People Functions') ?></h3>
    </div>
    <div class="box-body">
        <a href="<?= $sRootPath ?>/SelectList.php?mode=person" class="btn btn-app"><i class="fa fa-user"></i><?= _('All People') ?></a>
        <?php
        if ($sEmailLink) {
            // Add default email if default email has been set and is not already in string
            if (SystemConfig::getValue('sToEmailAddress') != '' && !stristr($sEmailLink, SystemConfig::getValue('sToEmailAddress'))) {
                $sEmailLink .= SessionUser::getUser()->MailtoDelimiter().SystemConfig::getValue('sToEmailAddress');
            }
            $sEmailLink = urlencode($sEmailLink);  // Mailto should comply with RFC 2368
            if (SessionUser::getUser()->isEmailEnabled()) { // Does user have permission to email groups
                // Display link
                ?>
                <div class="btn-group">
                    <a  class="btn btn-app" href="mailto:<?= mb_substr($sEmailLink, 0, -3) ?>"><i class="fa fa-send-o"></i><?= _('Email All')?></a>
                    <button type="button" class="btn btn-app dropdown-toggle" data-toggle="dropdown" >
                        <span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        <?= MiscUtils::generateGroupRoleEmailDropdown($roleEmails, 'mailto:') ?>
                    </ul>
                </div>
                <div class="btn-group">
                    <a class="btn btn-app" href="mailto:?bcc=<?= mb_substr($sEmailLink, 0, -3) ?>"><i class="fa fa-send"></i><?=_('Email All (BCC)') ?></a>
                    <button type="button" class="btn btn-app dropdown-toggle" data-toggle="dropdown" >
                        <span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        <?= MiscUtils::generateGroupRoleEmailDropdown($roleEmails, 'mailto:?bcc=') ?>
                    </ul>
                </div>
                <?php
            }
        }
        ?>
        <br/>
        <a href="<?= $sRootPath ?>/v2/familylist" class="btn btn-app"><i class="fa fa-users"></i><?= _('All Families') ?></a>
        <?php
        if (SessionUser::getUser()->isShowMapEnabled()) {
            ?>
            <a href="<?= $sRootPath ?>/GeoPage.php" class="btn btn-app"><i class="fa fa-globe"></i><?= _('Family Geographic') ?></a>
            <a href="<?= $sRootPath ?>/v2/map/-1" class="btn btn-app"><i class="fa fa-map"></i><?= _('Family Map') ?></a>
            <a href="<?= $sRootPath ?>/UpdateAllLatLon.php" class="btn btn-app"><i class="fa fa-map-pin"></i><?= _('Update All Family Coordinates') ?></a>
            <?php
        }
        ?>
    </div>
</div>
<!-- Small boxes (Stat box) -->
<div class="row">
    <div class="col-lg-3 col-md-6 col-sm-6">
        <!-- small box -->
        <div class="small-box bg-aqua">
            <div class="inner">
                <h3>
                    <?= $familyCount['familyCount'] ?>
                </h3>

                <p>
                    <?= _('Families') ?>
                </p>
            </div>
            <div class="icon">
                <i class="fa fa-users"></i>
            </div>
            <a href="<?= $sRootPath ?>/v2/familylist" class="small-box-footer">
                <?= _('See all Families') ?> <i class="fa fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-md-6 col-sm-6">
        <!-- small box -->
        <div class="small-box bg-purple">
            <div class="inner">
                <h3>
                    <?= $personCount['personCount'] ?>
                </h3>

                <p>
                    <?= _('People') ?>
                </p>
            </div>
            <div class="icon">
                <i class="fa fa-user"></i>
            </div>
            <a href="<?= $sRootPath ?>/SelectList.php?mode=person" class="small-box-footer">
                <?= _('See All People') ?> <i class="fa fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-md-6 col-sm-6">
        <!-- small box -->
        <div class="small-box bg-yellow">
            <div class="inner">
                <h3>
                    <?= $groupStats['sundaySchoolkids'] ?>
                </h3>

                <p>
                    <?= _('Sunday School Kids') ?>
                </p>
            </div>
            <div class="icon">
                <i class="fa fa-child"></i>
            </div>
            <a href="<?= $sRootPath ?>/v2/sundayschool/dashboard" class="small-box-footer">
                <?= _('More info') ?> <i class="fa fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-md-6 col-sm-6">
        <!-- small box -->
        <div class="small-box bg-maroon">
            <div class="inner">
                <h3>
                    <?= $groupStats['groups'] - $groupStats['sundaySchoolClasses'] ?>
                </h3>

                <p>
                    <?= _('Groups') ?>
                </p>
            </div>
            <div class="icon">
                <i class="fa fa-gg"></i>
            </div>
            <a href="<?= $sRootPath ?>/v2/group/list" class="small-box-footer">
                <?= _('More info') ?> <i class="fa fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <!-- ./col -->

</div><!-- /.row -->
<div class="row">
    <div class="col-lg-6">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title"><?= _('Reports') ?></h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                    <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                </div>
            </div>
            <div class="box-body">
                <a class="MediumText" href="<?= $sRootPath ?>/GroupReports.php"><?= _('Reports on groups and roles') ?></a>
                <br>
                <?= _('Report on group and roles selected (it may be a multi-page PDF).') ?>
                </p>
                <?php
                if (SessionUser::getUser()->isCreateDirectoryEnabled()) {
                    ?>
                    <p><a class="MediumText"
                          href="<?= $sRootPath ?>/DirectoryReports.php"><?= _('People Directory') ?></a><br><?= _('Printable directory of all people, grouped by family where assigned') ?>
                    </p>
                    <?php
                }
                ?>
                <?php
                if (SessionUser::getUser()->isFinanceEnabled()) {
                    ?>
                    <p><a class="MediumText"
                          href="<?= $sRootPath ?>/ReminderReport.php"><?= _('Pledge Reminder Report') ?></a><br><?= _('Printable Pledge Reminder of all people, grouped by family where assigned') ?>
                    </p>
                    <?php
                }
                ?>

                <a class="MediumText" href="<?= $sRootPath ?>/LettersAndLabels.php"><?= _('Letters and Mailing Labels') ?></a>
                <br><?= _('Generate letters and mailing labels.') ?>
                </p>
                <?php
                if (SessionUser::getUser()->isUSAddressVerificationEnabled()) {
                    ?>
                    <p>
                        <a class="MediumText" href="<?= $sRootPath ?>/USISTAddressVerification.php">
                            <?= _('US Address Verification Report') ?>
                        </a>
                        <br>
                        <?= _('Generate report comparing all US family addresses '.
                            'with United States Postal Service Standard Address Format.<br>') ?>
                    </p>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title"><?= _('Self Update') ?> <?= _('Reports') ?></h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                </div>
            </div>
            <div class="box-body">
                <p> <a class="MediumText" href="<?= $sRootPath ?>/members/self-register.php"><?= _('Self Register') ?> <?= _('Reports') ?></a>
                    <br>
                    <?= _('List families that were created via self registration.') ?>
                </p>
                <p>
                    <a class="MediumText"
                       href="<?= $sRootPath ?>/members/self-verify-updates.php"><?= _('Self Verify Updates') ?></a><br><?= _('Families who commented via self verify links') ?>
                </p>
                <p>
                    <a class="MediumText"
                       href="<?= $sRootPath ?>/members/online-pending-verify.php"><?= _('Pending Self Verify') ?></a><br><?= _('Families with valid self verify links') ?>
                </p>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <i class="fa fa-pie-chart"></i>

                <h3 class="box-title"><?= _('Family Roles') ?></h3>

                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                </div>
            </div>
            <div class="box-body no-padding">
                <table class="table table-condensed">
                    <tr>
                        <th><?= _('Role / Gender') ?></th>
                        <th>% <?= _('of People') ?></th>
                        <th style="width: 40px"><?= _('Count') ?></th>
                    </tr>
                    <?php foreach ($demographicStats as $demStat) {
                        ?>
                        <tr>
                            <td>
                                <a href="<?= $sRootPath ?>/SelectList.php?mode=person&Gender=<?= $demStat['gender'] ?>&FamilyRole=<?= $demStat['role'] ?>"><?= _($demStat['key']) ?></a>
                            </td>
                            <td>
                                <div class="progress progress-xs progress-striped active">
                                    <div class="progress-bar progress-bar-success"
                                         style="width: <?= round($demStat['value'] / $personCount['personCount'] * 100) ?>%"></div>
                                </div>
                            </td>
                            <td><span class="badge bg-green"><?= $demStat['value'] ?></span></td>
                        </tr>
                        <?php
                    } ?>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <i class="fa fa-bar-chart-o"></i>

                <h3 class="box-title"><?= _('People Classification') ?></h3>

                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                </div>
            </div>
            <table class="table table-condensed">
                <tr>
                    <th><?= _('Classification') ?></th>
                    <th>% <?= _('of People') ?></th>
                    <th style="width: 40px"><?= _('Count') ?></th>
                </tr>
                <?php foreach ($personStats as $key => $value) {
                    ?>
                    <tr>
                        <td><a href='<?= $sRootPath ?>/SelectList.php?Sort=name&Filter=&mode=person&Classification=<?= $classifications->$key ?>'><?= _($key) ?></a></td>
                        <td>
                            <div class="progress progress-xs progress-striped active">
                                <div class="progress-bar progress-bar-success"
                                     style="width: <?= round($value / $personCount['personCount'] * 100) ?>%"></div>
                            </div>
                        </td>
                        <td><span class="badge bg-green"><?= $value ?></span></td>
                    </tr>
                    <?php
                } ?>
            </table>
            <!-- /.box-body-->
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-6">
        <div class="box box-info">
            <div class="box-header with-border">
                <i class="fa fa-address-card-o"></i>

                <h3 class="box-title"><?= _('Gender Demographics') ?></h3>

                <div class="box-tools pull-right">
                    <div id="gender-donut-legend" class="chart-legend"></div>
                </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <canvas id="gender-donut" style="height:250px"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="box box-info">
            <div class="box-header with-border">
                <i class="fa fa-birthday-cake"></i>

                <h3 class="box-title"><?= _('# Age Histogram')?></h3>

                <div class="box-tools pull-left">
                    <div id="age-stats-bar-legend" class="chart-legend"></div>
                </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <canvas id="age-stats-bar" style="height:250px"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- this page specific inline scripts -->
<script nonce="<?= $sCSPNonce ?>">
    $(document).ready(function () {
        //-------------
        //- PIE CHART -
        //-------------
        // Get context with jQuery - using jQuery's .get() method.
        var PieData = <?php
            $Labels          = [];
            $Datas           = [];
            $BackgroundColor = [];
            $borderColor     = [];

            foreach ($adultsGender as $adultGender) {
                if ($adultGender->getGender() == 1) {
                    $Labels[]           = _('Men');
                    $Datas[]            = $adultGender->getNumb();
                    $BackgroundColor[]  = "#003399";
                    $borderColor[]      = "#3366ff";
                } else if ($adultGender->getGender() == 2) {
                    $Labels[]           = _('Women');
                    $Datas[]            = $adultGender->getNumb();
                    $BackgroundColor[]  = "#9900ff";
                    $borderColor[]      = "#ff66cc";
                }
            }

            foreach ($kidsGender as $kidGender) {
                if ($kidGender->getGender() == 1) {
                    $Labels[]           = _('Boys');
                    $Datas[]            = $kidGender->getNumb();
                    $BackgroundColor[]  = "#3399ff";
                    $borderColor[]      = "#99ccff";
                } else if ($kidGender->getGender() == 2) {
                    $Labels[]           = _('Girls');
                    $Datas[]            = $kidGender->getNumb();
                    $BackgroundColor[]  = "#009933";
                    $borderColor[]      = "#99cc00";
                }
            }

            $datasets = new StdClass();

            $datasets->label           = '# of Votes';
            $datasets->data            = $Datas;
            $datasets->backgroundColor = $BackgroundColor;
            $datasets->borderColor     = $borderColor;
            $datasets->borderWidth     = 1;


            $res = new StdClass();

            $res->datasets   = [];
            $res->datasets[] = $datasets;
            $res->labels     = $Labels;

            echo json_encode($res,JSON_NUMERIC_CHECK);
            ?>;

        var pieOptions = {
            animation: {animateRotate: true, animateScale: false},
            circumference: 6.283185307179586,
            cutoutPercentage: 50,
            hover: {mode: "single"},
            rotation: -1.5707963267948966
        };

        var pieChartCanvas = $("#gender-donut").get(0).getContext("2d");
        var pieChart = new Chart(pieChartCanvas,{
            type: 'doughnut',
            data: PieData,
            options: pieOptions
        });

        //then you just need to generate the legend
        //var legend = pieChart.generateLegend();

        //and append it to your page somewhere
        //$('#gender-donut-legend').append(legend);

        var histDatas = <?php
            $Labels = [];
            $Datas  = [];
            $BackgroundColor = [];
            $borderColor     = [];

            foreach ($ageStats as $age => $value) {
                $datasets = new StdClass();

                $datasets->x  = $age;
                $datasets->y  = $value;

                $Labels[] = $age;
                $Datas[]  = $datasets;
                $BackgroundColor[]  = "#86adc4";
                $borderColor[]      = "#337ab7";
            }

            $datasets = new StdClass();

            $datasets->label           = _('# number of people');
            $datasets->data            = $Datas;
            $datasets->backgroundColor = $BackgroundColor;
            $datasets->borderColor     = $borderColor;
            $datasets->borderWidth     = 1;


            $res = new StdClass();

            $res->datasets   = [];
            $res->datasets[] = $datasets;
            $res->labels     = $Labels;

            echo json_encode($res,JSON_NUMERIC_CHECK);
            ?>;

        var ageStatsCanvas = $("#age-stats-bar").get(0).getContext("2d");

        var AgeChart = new Chart(ageStatsCanvas,{
            type: 'bar',
            data: histDatas,
            options: {
                scales: {
                    xAxes: [{
                        display: true,
                        barPercentage: 1.3,
                        ticks: {
                            max: 100,
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero:true
                        }
                    }]
                }
            }
        });



    });
</script>

<?php require $sRootDocument . '/Include/Footer.php'; ?>