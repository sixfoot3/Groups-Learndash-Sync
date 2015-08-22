<?php if ( !defined( 'ABSPATH' ) ) die(); ?>

<div class="wrap">
    <?php screen_icon('page'); ?>
    <h2>
        <?php _e('Settings', 'sf3_GroupsLearndashSync'); ?>
    </h2>
    <?php settings_errors(); ?>

    <div id="settings">
        <form id="settings" class="" method="post" action="options.php">
            <?php
                settings_fields('sf3_glsync');
                do_settings_sections('settings_area');
                submit_button( _('Save'));
            ?>
        </form>
    </div>
</div>