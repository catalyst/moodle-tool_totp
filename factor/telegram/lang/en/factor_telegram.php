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
 * Language strings.
 *
 * @package     factor_telegram
 * @author      Jan Dageförde, Laura Troost
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Telegram One-Time Code';
$string['action:revoke'] = 'Revoke Telegram ID';
$string['addtelegramid'] = 'Enter Telegram username or ID';
$string['info'] = '<p>Setup your Telegram ID so that one-time security codes can be sent to you.</p>';
$string['loginsubmit'] = 'Verify code';
$string['loginskip'] = "I didn't receive a code";
$string['setupfactor'] = 'Setup Telegram ID';
$string['settings:duration'] = 'Validity duration';
$string['settings:duration_help'] = 'The period of time that the code is valid.';
$string['settings:telegrambottoken'] = 'Token of your Telegram bot';
$string['settings:telegrambottoken_help'] = 'Register a new Telegram bot <a href="https://core.telegram.org/bots#6-botfather">as described in the Telegram documentation</a> and enter its token here.';
$string['settings:telegrambotname'] = 'Username of your Telegram bot';
$string['settings:telegrambotname_help'] = 'Enter the username of your bot (e. g., @Moodlebot), as entered during the registration. This name will be displayed to users so that they can set up their own factor.';
$string['telegramhelp'] = 'First, send a message containing the text "/start" to <a href="https://t.me/{$a}">@{$a}</a>. Afterwards(!), enter your Telegram user name or, alternatively, <a href="https://telegram.me/userinfobot">your Telegram user ID</a>.';
$string['telegramsent'] = 'A Telegram message containing your verification code was sent to you ({$a}).';
$string['telegramstring'] = '{$a->code} is your {$a->fullname} one-time security code.

@{$a->url} #{$a->code}';
$string['summarycondition'] = 'Using a one-time security code sent via Telegram';
$string['privacy:metadata'] = 'The Telegram factor plugin does not store any personal data';
$string['wrongcode'] = 'Invalid security code.';
