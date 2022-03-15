<?php
/*******************************************************************************
 *
 *  filename    : notinmailchimpemailsfamilies.php
 *  last change : 2019/2/6
 *  website     : http://www.ecclesiacrm.com
 *  copyright   : Copyright 2019/2/6 Philippe Logel
 *
 ******************************************************************************/

require $sRootDocument . '/Include/Header.php';
?>

<div class="card">
    <div class="card-header  border-1">
        <h3 class="card-title"><?= _("Person List") ?></h3>
        <div style="float:right">
            <a href="https://mailchimp.com/<?= $lang ?>/"><img
                    class="logo-mailchimp"  src="<?= $sRootPath ?>/Images/<?= \EcclesiaCRM\Theme::isDarkModeEnabled()?'Mailchimp_Logo-Horizontal_White.png':'Mailchimp_Logo-Horizontal_Black.png' ?>" height=25/></a>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-striped table-bordered" id="personsWithoutEmailTable" cellpadding="5" cellspacing="0"
               width="100%"></table>
    </div>
</div>

<script src="<?= $sRootPath ?>/skin/js/email/MailChimp/AutomaticDarkMode.js"></script>

<?php require $sRootDocument . '/Include/Footer.php'; ?>

<script src="<?= $sRootPath ?>/skin/js/email/MailChimp/NotInMailChimpPersons.js"></script>


