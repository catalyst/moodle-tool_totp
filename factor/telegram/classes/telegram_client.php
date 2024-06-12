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
 * Telegram API client.
 *
 * @package     factor_telegram
 * @subpackage  tool_mfa
 * @author      Jan Dageförde, Laura Troost
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace factor_telegram;

defined('MOODLE_INTERNAL') || die();

/**
 * Telegram API client.
 * @package factor_telegram
 */
class telegram_client {

    private $token;

    public function __construct($token) {
        $this->token = $token;
    }

    public function send_message($userid, $text) {
        $params = [
            'chat_id' => $userid,
            'text' => $text,
        ];

        $httpclient = new \curl();
        $httpclient->get("https://api.telegram.org/bot" . $this->token . "/sendmessage", $params);
    }
}