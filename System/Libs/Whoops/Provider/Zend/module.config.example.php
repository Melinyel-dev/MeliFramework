<?php
/**
 * ZF2 Integration for Whoops
 * @author Balázs Németh <zsilbi@zsilbi.hu>
 *
 * Example controller configuration
 */

return array(
    'view_manager' => array(
        'editor' => 'sublime',
        'display_not_found_reason' => TRUE,
        'display_exceptions' => TRUE,
        'json_exceptions' => array(
            'display' => TRUE,
            'ajax_only' => TRUE,
            'show_trace' => TRUE
        )
    ),
);
