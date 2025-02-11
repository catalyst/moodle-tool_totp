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
 * Tests for TOTP factor.
 *
 * @covers      \factor_email\factor
 * @package     factor_email
 * @author      Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright   Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class email_factor_test extends \core_phpunit\testcase {

    /**
     * Provides to test_generate_email_ip_address_location test.
     * @return array
     */
    public function generate_email_ip_address_location_provider(): array {
        return [
            'real ip v4' => [
                // Note - this is the same IP address used by core_iplookup_geoplugin_testcase.
                'ip' => '50.0.184.0'
            ],
            'real ip v6' => [
                // Ipv6 is not supported by geoplugin, so it should be treated as unknown.
                'ip' => '2a01:8900:2:3:8c6c:c0db:3d33:9ce6'
            ],
            'empty ip' => [
                'ip' => ''
            ],
            'malformed ip' => [
                'ip' => '1.1.1.1.1.1.1.1.1.1'
            ],
            'localhost' => [
                'ip' => '0.0.0.0'
            ],
            'malformed ip 2' => [
                'ip' => 'aaaaaa'
            ]
        ];
    }

    /**
     * Tests the rendererer generate_email function with regard to its
     *
     * @param string $ip IP address to test
     *
     * @dataProvider generate_email_ip_address_location_provider
     */
    public function test_generate_email_ip_address_location(string $ip) {
        global $DB, $PAGE;
        $this->resetAfterTest(true);

        // Setup user and email factor.
        $user = $this->getDataGenerator()->create_user();
        set_config('enabled', 1, 'factor_email');
        $emailfactor = \tool_mfa\plugininfo\factor::get_factor('email');

        // Manually insert email factor record so that we can edit the IP address.
        $record = [
            'userid' => $user->id,
            'factor' => $emailfactor->name,
            'label' => $user->email,
            'createdfromip' => $ip,
            'timecreated' => time(),
            'revoked' => 0,
        ];
        $record['id'] = $DB->insert_record('tool_mfa', $record, true);

        $renderer = $PAGE->get_renderer('factor_email');
        $email = $renderer->generate_email($record['id']);

        // Debugging will be called by the curl to the geoplugin service failing.
        // We ignore this in this unit test.
        $this->resetDebugging();

        // Note it's difficult to know beforehand where a IP address will resolve to.
        // So instead, we just check that it contains EITHER a location or an unknown message.
        $containslocation = strpos($email, 'This request appears to have originated from approximately') != false;
        $containsunknown = strpos($email, get_string('email:geoinfo:unknown', 'factor_email')) != false;
        $this->assertTrue($containslocation || $containsunknown);
    }
}
