<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Telegram Factor class.
 *
 * @package     factor_telegram
 * @subpackage  tool_mfa
 * @author      Jan Dageförde, Laura Troost
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace factor_telegram;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use tool_mfa\local\factor\object_factor_base;

class factor extends object_factor_base {
    /**
     * Login form: Definition.
     *
     * {@inheritDoc}
     */
    public function login_form_definition($mform) {
        $mform->addElement(new \tool_mfa\local\form\verification_field());
        $mform->setType('verificationcode', PARAM_ALPHANUM);
        return $mform;
    }

    /**
     * Login form: Send a secret to the user and add a corresponding note to the form.
     *
     * {@inheritDoc}
     */
    public function login_form_definition_after_data($mform) {
        $recipient = $this->generate_and_send_code();
        $mform->addElement('html', \html_writer::tag('p', get_string('telegramsent', 'factor_telegram', $recipient) . '<br>'));
        // Disable the form check prompt.
        $mform->disable_form_change_checker();
        return $mform;
    }

    /**
     * Login form: Validate secret.
     *
     * {@inheritDoc}
     */
    public function login_form_validation($data) {
        $return = array();

        if (!$this->check_verification_code($data['verificationcode'])) {
            $return['verificationcode'] = get_string('wrongcode', 'factor_telegram');
        }

        return $return;
    }

    /**
     * Gets the string for setup button on preferences page.
     */
    public function get_setup_string() {
        return get_string('setupfactor', 'factor_telegram');
    }

    /**
     * Requires a bot token to be *actually* enabled.
     *
     * {@inheritDoc}
     */
    public function is_enabled() {
        if (empty(get_config('factor_telegram', 'telegrambottoken'))) {
            return false;
        } else {
            return parent::is_enabled();
        }
    }

    /**
     * Requires user input to verify.
     *
     * {@inheritDoc}
     */
    public function has_input() {
        return true;
    }

    /**
     * Requires user setup.
     *
     * {@inheritDoc}
     */
    public function has_setup() {
        return true;
    }

    /**
     * If there is already a factor setup, don't allow multiple (for now).
     *
     * {@inheritDoc}
     */
    public function show_setup_buttons() {
        global $DB, $USER;
        $sql = 'SELECT *
                  FROM {tool_mfa}
                 WHERE userid = ?
                   AND factor = ?
                   AND secret = ?
                   AND revoked = 0';

        $record = $DB->get_record_sql($sql, [$USER->id, $this->name, '']);
        return !empty($record) ? false : true;
    }

    /**
     * A factor can be revoked by a user.
     *
     * {@inheritDoc}
     */
    public function has_revoke() {
        return true;
    }

    /**
     * Verifies entered code against stored DB record.
     *
     * @return bool
     */
    private function check_verification_code($enteredcode) {
        $state = $this->secretmanager->validate_secret($enteredcode);
        if ($state === \tool_mfa\local\secret_manager::VALID) {
            return true;
        }
        return false;
    }

    /**
     * The Telegram factor can assume one of these states.
     *
     * {@inheritDoc}
     */
    public function possible_states($user) {
        return array(
            \tool_mfa\plugininfo\factor::STATE_PASS,
            \tool_mfa\plugininfo\factor::STATE_NEUTRAL,
            \tool_mfa\plugininfo\factor::STATE_UNKNOWN,
        );
    }

    /**
     * Get all Telegram IDs of a user.
     *
     * {@inheritDoc}
     */
    public function get_all_user_factors($user) {
        global $DB;

        $sql = 'SELECT *
                  FROM {tool_mfa}
                 WHERE userid = ?
                   AND factor = ?
                   AND label IS NOT NULL
                   AND revoked = 0';

        return $DB->get_records_sql($sql, [$user->id, $this->name]);
    }

    /**
     * User factor: Form definition.
     *
     * {@inheritDoc}
     */
    public function setup_factor_form_definition($mform) {
        global $SESSION, $OUTPUT;

        $mform->addElement('html', $OUTPUT->heading(get_string('setupfactor', 'factor_telegram'), 2));

        // The field $SESSION->tool_mfa_telegram_id temporarily stores an ID that the user has entered, but not yet verified.
        if (empty($SESSION->tool_mfa_telegram_id)) {
            $mform->addElement('hidden', 'verificationcode', 0);
            $mform->setType("verificationcode", PARAM_ALPHANUM);

            // Field that specifies the user's Telegram ID.
            $mform->addElement('text', 'telegramid', get_string('addtelegramid', 'factor_telegram'));
            $mform->setType('telegramid', PARAM_TEXT);
            $botname = get_config('factor_telegram', 'telegrambotname');
            if (strpos($botname, '@') === 0) {
                $botname = substr($botname, 1);
            }
            $mform->addElement('html', \html_writer::tag('p', get_string('telegramhelp', 'factor_telegram', $botname)));
        }
    }

    /**
     * User factor: Form definition after data.
     *
     * {@inheritDoc}
     */
    public function setup_factor_form_definition_after_data($mform) {
        global $SESSION;

        // Do nothing before a Telegram ID has been entered.
        if (empty($SESSION->tool_mfa_telegram_id)) {
            return $mform;
        }

        // Once there is a supplied Telegram ID, send a verification code to set up the factor.
        $mform->addElement(new \tool_mfa\local\form\verification_field());
        $mform->setType('verificationcode', PARAM_ALPHANUM);

        $duration = get_config('factor_telegram', 'duration');
        $code = $this->secretmanager->create_secret($duration, true);
        if (!empty($code)) {
            $this->send_verification_code($code, $SESSION->tool_mfa_telegram_id);
        }

        // Tell users it was sent.
        $mform->addElement('html', \html_writer::tag('p',
            get_string('telegramsent', 'factor_telegram', $SESSION->tool_mfa_telegram_id) . '<br>'));

        // Disable the form check prompt.
        $mform->disable_form_change_checker();
    }

    /**
     * User factor: Form validation.
     *
     * {@inheritDoc}
     */
    public function setup_factor_form_validation($data) {
        global $SESSION;

        // No validation on the initial ID (i.e., after step 1).
        if (empty($SESSION->tool_mfa_telegram_id)) {
            return [];
        }

        // Validate the trial secret.
        $errors = [];
        $result = $this->secretmanager->validate_secret($data['verificationcode']);
        if ($result !== $this->secretmanager::VALID) {
            $errors['verificationcode'] = get_string('wrongcode', 'factor_telegram');
        }

        return $errors;
    }

    /**
     * User factor: Form submission.
     * Either stores the Telegram ID in a temporary session variable (awaiting manual verification),
     * or in the database (after verification succeeded).
     *
     * {@inheritDoc}
     */
    public function setup_user_factor($data) {
        global $DB, $SESSION, $USER;

        // Initial submission of ID: Store in session; redirect for step 2 (verification).
        if (empty($SESSION->tool_mfa_telegram_id)) {
            $SESSION->tool_mfa_telegram_id = $data->telegramid;

            $addurl = new \moodle_url('/admin/tool/mfa/action.php', [
                'action' => 'setup',
                'factor' => 'telegram',
            ]);
            redirect($addurl);
        }

        // Step 2 (verification succeeded): Store permanently.

        // If the user somehow gets here through form resubmission.
        // We dont want two phones active.
        if ($DB->record_exists('tool_mfa', ['userid' => $USER->id, 'factor' => $this->name, 'revoked' => 0])) {
            return null;
        }

        $row = new \stdClass();
        $row->userid = $USER->id;
        $row->factor = $this->name;
        $row->secret = '';
        $row->label = $SESSION->tool_mfa_telegram_id;
        $row->timecreated = time();
        $row->createdfromip = $USER->lastip;
        $row->timemodified = time();
        $row->lastverified = time();
        $row->revoked = 0;

        $id = $DB->insert_record('tool_mfa', $row);
        $record = $DB->get_record('tool_mfa', array('id' => $id));
        $this->create_event_after_factor_setup($USER);

        // Remove ID from temporary session variable.
        unset($SESSION->tool_mfa_telegram_id);

        return $record;
    }

    /**
     * Generates and sends the code for login to the user, stores codes in DB.
     *
     * @return int the instance ID being used.
     */
    private function generate_and_send_code() {
        global $DB, $USER;

        $duration = get_config('factor_telegram', 'duration');
        $secret = $this->secretmanager->create_secret($duration, false);
        $instance = $DB->get_record('tool_mfa', ['factor' => $this->name, 'userid' => $USER->id, 'revoked' => 0]);

        // There is a new code that needs to be sent.
        if (!empty($secret)) {
            // Grab the singleton SMS record.
            $this->send_verification_code($secret, $instance->label);
        }
        return $instance->label;
    }

    /**
     * This function sends a code to the user via Telegram.
     *
     * @param int $secret the secret to send.
     * @param string $telegramid Recipient user id.
     * @return void
     */
    private function send_verification_code($secret, $telegramid) {
        global $CFG, $SITE;

        // Here we should get the information, then construct the message.
        $url = new moodle_url($CFG->wwwroot);
        $content = [
            'fullname' => $SITE->fullname,
            'shortname' => $SITE->shortname,
            'supportname' => $CFG->supportname,
            'url' => $url->get_host(),
            'code' => $secret];
        $message = get_string('telegramstring', 'factor_telegram', $content);

        $bottoken = get_config('factor_telegram', 'telegrambottoken');
        $client = new \factor_telegram\telegram_client($bottoken);
        $client->send_message($telegramid, $message);
    }
}
