<?php

/**
 * SquirrelMail time zone library
 *
 * Used ftp://elsie.nci.nih.gov/pub/tzdata2005j.tar.gz as reference
 *
 * Time zone array must consist of key name that matches time zone name in
 * GNU C library and 'LINK', 'NAME' and 'TZ' subkeys. 'LINK' subkey is used
 * to define time zone aliases ('Link some/name other/name' in GNU C). It 
 * should link to other time zone array entry with 'NAME' and 'TZ' subkeys.
 * Linking to 'LINK' entries will cause errors in time zone library checks. 
 * 'NAME' key should store translatable time zone name. 'TZ' key should store
 * time zone name that will be used in TZ environment variable. Array entries 
 * can use 'LINK' or 'TZ' subkeys. 'LINK' and 'TZ' subkeys should not be used
 * in same array key. 'NAME' subkeys are optional and used only in display 
 * of 'TZ' key entries.
 *
 * @link ftp://elsie.nci.nih.gov/pub/ GNU C time zone implementation
 * @link some source of POSIX TZ names
 * @copyright 2005-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage timezones
 */

/**
 * Standard timezone array.
 *
 * TZ subkeys must be updated if some government decides to change timezone.
 *
 * Generic abbreviations:
 * <ul>
 *  <li>GMT - Greenwich Mean Time
 *  <li>UTC - Coordinated Universal Time
 *  <li>UCT - Universal Coordinate Time
 * </ul>
 * 
 * Array is not globalized in order to save memory. Used array is extracted 
 * with sq_get_tz_array(). Array is loaded only when strict time zone is set
 * or personal information option page is loaded. 'timezones' gettext domain
 * must be set before loading this array.
 * @global array $aTimeZones
 */
$aTimeZones=array();

/** Africa **/
$aTimeZones['Africa/Algiers']['NAME']='Algeria';
$aTimeZones['Africa/Algiers']['TZ']='CET';
$aTimeZones['Africa/Luanda']['NAME']='Angola';
$aTimeZones['Africa/Luanda']['TZ']='UCT-1';
$aTimeZones['Africa/Porto-Novo']['NAME']='Benin';
$aTimeZones['Africa/Porto-Novo']['TZ']='UCT-1';
$aTimeZones['Africa/Gaborone']['NAME']='Botswana';
$aTimeZones['Africa/Gaborone']['TZ']='UCT-2';
$aTimeZones['Africa/Ouagadougou']['NAME']='Burkina Faso';
$aTimeZones['Africa/Ouagadougou']['TZ']='GMT';
$aTimeZones['Africa/Bujumbura']['NAME']='Burundi';
$aTimeZones['Africa/Bujumbura']['TZ']='UCT-2';
$aTimeZones['Africa/Douala']['NAME']='Cameroon';
$aTimeZones['Africa/Douala']['TZ']='UCT-1';
$aTimeZones['Atlantic/Cape_Verde']['NAME']='Cape Verde';
$aTimeZones['Atlantic/Cape_Verde']['TZ']='UCT1';
$aTimeZones['Africa/Bangui']['NAME']='Central African Republic';
$aTimeZones['Africa/Bangui']['TZ']='UCT-1';
$aTimeZones['Africa/Ndjamena']['NAME']='Chad';
$aTimeZones['Africa/Ndjamena']['TZ']='UCT-1';
$aTimeZones['Indian/Comoro']['NAME']='Comoros';
$aTimeZones['Indian/Comoro']['TZ']='UCT-3';
$aTimeZones['Africa/Kinshasa']['NAME']='Democratic Republic of Congo, Kinshasa';
$aTimeZones['Africa/Kinshasa']['TZ']='UCT-1';
$aTimeZones['Africa/Lubumbashi']['NAME']='Democratic Republic of Congo';
$aTimeZones['Africa/Lubumbashi']['TZ']='UCT-2';
$aTimeZones['Africa/Brazzaville']['NAME']='Republic of the Congo';
$aTimeZones['Africa/Brazzaville']['TZ']='UCT-1';
$aTimeZones['Africa/Abidjan']['NAME']='Cote D\'Ivoire';
$aTimeZones['Africa/Abidjan']['TZ']='GMT';
$aTimeZones['Africa/Djibouti']['NAME']='Djibouti';
$aTimeZones['Africa/Djibouti']['TZ']='UCT-3';

// Daylight savings between Apr lastFri 0:00 and Sep lastThu 23:00 (+1)
$aTimeZones['Africa/Cairo']['NAME']='Egypt';
$aTimeZones['Africa/Cairo']['TZ']='EST-2EDT';

$aTimeZones['Africa/Malabo']['NAME']='Equatorial Guinea';
$aTimeZones['Africa/Malabo']['TZ']='UCT-1';
$aTimeZones['Africa/Asmera']['NAME']='Eritrea';
$aTimeZones['Africa/Asmera']['TZ']='UCT-3';
$aTimeZones['Africa/Addis_Ababa']['NAME']='Ethiopia';
$aTimeZones['Africa/Addis_Ababa']['TZ']='UCT-3';
$aTimeZones['Africa/Libreville']['NAME']='Gabon';
$aTimeZones['Africa/Libreville']['TZ']='UCT-1';
$aTimeZones['Africa/Banjul']['NAME']='Gambia';
$aTimeZones['Africa/Banjul']['TZ']='GMT';
$aTimeZones['Africa/Accra']['NAME']='Ghana';
$aTimeZones['Africa/Accra']['TZ']='UCT';
$aTimeZones['Africa/Conakry']['NAME']='Guinea';
$aTimeZones['Africa/Conakry']['TZ']='GMT';
$aTimeZones['Africa/Bissau']['NAME']='Guinea-Bissau';
$aTimeZones['Africa/Bissau']['TZ']='GMT';
$aTimeZones['Africa/Nairobi']['NAME']='Kenya';
$aTimeZones['Africa/Nairobi']['TZ']='UCT-3';
$aTimeZones['Africa/Maseru']['NAME']='Lesotho';
$aTimeZones['Africa/Maseru']['TZ']='UCT-2';
$aTimeZones['Africa/Monrovia']['NAME']='Liberia';
$aTimeZones['Africa/Monrovia']['TZ']='GMT';
$aTimeZones['Africa/Tripoli']['NAME']='Libya';
$aTimeZones['Africa/Tripoli']['TZ']='UCT-2';
$aTimeZones['Indian/Antananarivo']['NAME']='Madagascar';
$aTimeZones['Indian/Antananarivo']['TZ']='UCT-3';
$aTimeZones['Africa/Blantyre']['NAME']='Malawi';
$aTimeZones['Africa/Blantyre']['TZ']='UCT-2';
$aTimeZones['Africa/Bamako']['NAME']='Mali';
$aTimeZones['Africa/Bamako']['TZ']='GMT';
$aTimeZones['Africa/Timbuktu']['NAME']='Mali, Timbuktu';
$aTimeZones['Africa/Timbuktu']['TZ']='GMT';
$aTimeZones['Africa/Nouakchott']['NAME']='Mauritania';
$aTimeZones['Africa/Nouakchott']['TZ']='GMT';
$aTimeZones['Indian/Mauritius']['NAME']='Mauritius';
$aTimeZones['Indian/Mauritius']['TZ']='UCT-4';
$aTimeZones['Indian/Mayotte']['NAME']='Mayotte';
$aTimeZones['Indian/Mayotte']['TZ']='UCT-3';
$aTimeZones['Africa/Casablanca']['NAME']='Morocco';
$aTimeZones['Africa/Casablanca']['TZ']='UCT';
$aTimeZones['Africa/El_Aaiun']['NAME']='Western Sahara';
$aTimeZones['Africa/El_Aaiun']['TZ']='UCT';
$aTimeZones['Africa/Maputo']['NAME']='Mozambique';
$aTimeZones['Africa/Maputo']['TZ']='UCT-2';

// Daylight savings from Sep Sun>=1 2:00 to Apr Sun>=1 2:00 (+1)
$aTimeZones['Africa/Windhoek']['NAME']='Namibia';
$aTimeZones['Africa/Windhoek']['TZ']='UCT-2';

$aTimeZones['Africa/Niamey']['NAME']='Niger';
$aTimeZones['Africa/Niamey']['TZ']='UCT-1';
$aTimeZones['Africa/Lagos']['NAME']='Nigeria';
$aTimeZones['Africa/Lagos']['TZ']='UCT-1';

// Island in Indian ocean
$aTimeZones['Indian/Reunion']['NAME']='Reunion';
$aTimeZones['Indian/Reunion']['TZ']='UCT-4';

$aTimeZones['Africa/Kigali']['NAME']='Rwanda';
$aTimeZones['Africa/Kigali']['TZ']='UCT-2';
$aTimeZones['Atlantic/St_Helena']['NAME']='St.Helena';
$aTimeZones['Atlantic/St_Helena']['TZ']='GMT';
$aTimeZones['Africa/Sao_Tome']['NAME']='Sao Tome and Principe';
$aTimeZones['Africa/Sao_Tome']['TZ']='GMT';
$aTimeZones['Africa/Dakar']['NAME']='Senegal';
$aTimeZones['Africa/Dakar']['TZ']='GMT';
$aTimeZones['Indian/Mahe']['NAME']='Seychelles';
$aTimeZones['Indian/Mahe']['TZ']='UCT-4';
$aTimeZones['Africa/Freetown']['NAME']='Sierra Leone';
$aTimeZones['Africa/Freetown']['TZ']='UCT';
$aTimeZones['Africa/Mogadishu']['NAME']='Somalia';
$aTimeZones['Africa/Mogadishu']['TZ']='UCT-3';
$aTimeZones['Africa/Johannesburg']['NAME']='South Africa';
$aTimeZones['Africa/Johannesburg']['TZ']='SAST-2';
$aTimeZones['Africa/Khartoum']['NAME']='Sudan';
$aTimeZones['Africa/Khartoum']['TZ']='UCT-3';
$aTimeZones['Africa/Mbabane']['NAME']='Swaziland';
$aTimeZones['Africa/Mbabane']['TZ']='UCT-2';
$aTimeZones['Africa/Dar_es_Salaam']['NAME']='Tanzania';
$aTimeZones['Africa/Dar_es_Salaam']['TZ']='UCT-3';
$aTimeZones['Africa/Lome']['NAME']='Togo';
$aTimeZones['Africa/Lome']['TZ']='GMT';
$aTimeZones['Africa/Tunis']['NAME']='Tunisia';
$aTimeZones['Africa/Tunis']['TZ']='UCT-1';
$aTimeZones['Africa/Kampala']['NAME']='Uganda';
$aTimeZones['Africa/Kampala']['TZ']='UCT-3';
$aTimeZones['Africa/Lusaka']['NAME']='Zambia';
$aTimeZones['Africa/Lusaka']['TZ']='UCT-2';
$aTimeZones['Africa/Harare']['NAME']='Zimbabwe';
$aTimeZones['Africa/Harare']['TZ']='UCT-2';

/** do we have squirrels in Antarctica */
$aTimeZones['Antarctica/Casey']['NAME']='Antarctica, Casey';
$aTimeZones['Antarctica/Casey']['TZ']='WST'; // (GMT+8) Western (Aus) Standard Time
$aTimeZones['Antarctica/Davis']['NAME']='Antarctica, Davis';
$aTimeZones['Antarctica/Davis']['TZ']='DAVT'; // (GMT+7) Davis Time
$aTimeZones['Antarctica/Mawson']['NAME']='Antarctica, Mawson';
$aTimeZones['Antarctica/Mawson']['TZ']='MAWT'; // (GMT+6) Mawson Time
$aTimeZones['Indian/Kerguelen']['NAME']='Antarctica, Kerquelen Island';
$aTimeZones['Indian/Kerguelen']['TZ']='TFT'; // (GMT+5) ISO code TF Time
$aTimeZones['Antarctica/DumontDUrville']['NAME']="Antarctica, Dumont-d'Urville";
$aTimeZones['Antarctica/DumontDUrville']['TZ']='DDUT'; // (GMT+10) Dumont-d'Urville Time
$aTimeZones['Antarctica/Syowa']['NAME']='Antarctica, Syowa';
$aTimeZones['Antarctica/Syowa']['TZ']='SYOT'; // (GMT+0300) Syowa Time
$aTimeZones['Antarctica/Vostok']['NAME']='Antarctica, Vostok';
$aTimeZones['Antarctica/Vostok']['TZ']='UTC+6'; // (GMT+6) VOST Vostok time
$aTimeZones['Antarctica/Rothera']['NAME']='Antarctica, Rothera';
$aTimeZones['Antarctica/Rothera']['TZ']='ROTT'; // (GMT-3) Rothera time
// ChileAQ daylight saving rules
// 1999    max     -       Oct     Sun>=9  0:00    1:00    S
// 2000    max     -       Mar     Sun>=9  0:00    0       -
$aTimeZones['Antarctica/Palmer']['NAME']='Antarctica, Palmer';
$aTimeZones['Antarctica/Palmer']['TZ']='CLT'; // (GMT-4)
// NZAQ daylight saving rules
// 1990    max     -       Oct     Sun>=1  2:00s   1:00    D
// 1990    max     -       Mar     Sun>=15 2:00s   0       S
$aTimeZones['Antarctica/McMurdo']['NAME']='Antarctica, McMurdo';
$aTimeZones['Antarctica/McMurdo']['TZ']='NZT'; // (GMT+12)
// same as McMurdo
$aTimeZones['Antarctica/South_Pole']['NAME']='Antarctica, South Pole';
$aTimeZones['Antarctica/South_Pole']['TZ']='NZT';

/** Asia **/
$aTimeZones['Asia/Kabul']['NAME']='Afghanistan';
$aTimeZones['Asia/Kabul']['TZ']='UCT-4:30';
// RussiaAsia daylight saving rules
$aTimeZones['Asia/Yerevan']['NAME']='Armenia';
$aTimeZones['Asia/Yerevan']['TZ']='UCT-4';
// Azer daylight saving rules
// 1997    max     -       Mar     lastSun  1:00   1:00    S
// 1997    max     -       Oct     lastSun  1:00   0       -
$aTimeZones['Asia/Baku']['NAME']='Azerbaijan';
$aTimeZones['Asia/Baku']['TZ']='UCT-3';

$aTimeZones['Asia/Bahrain']['NAME']='Bahrain';
$aTimeZones['Asia/Bahrain']['TZ']='UCT-3';

$aTimeZones['Asia/Dhaka']['NAME']='Bangladesh';
$aTimeZones['Asia/Dhaka']['TZ']='UCT-6';

$aTimeZones['Asia/Thimphu']['NAME']='Bhutan';
$aTimeZones['Asia/Thimphu']['TZ']='UCT-6';

$aTimeZones['Indian/Chagos']['NAME']='British Indian Ocean Territory';
$aTimeZones['Indian/Chagos']['TZ']='UCT-6';

$aTimeZones['Asia/Brunei']['NAME']='Brunei';
$aTimeZones['Asia/Brunei']['TZ']='UCT-8';

// Burma
$aTimeZones['Asia/Rangoon']['NAME']='Myanmar';
$aTimeZones['Asia/Rangoon']['TZ']='UCT-6:30';

$aTimeZones['Asia/Phnom_Penh']['NAME']='Cambodia';
$aTimeZones['Asia/Phnom_Penh']['TZ']='UCT-7';

// China (PRC) - one timezone to rule them all
//  Changbai Time (Long-white Time)
$aTimeZones['Asia/Harbin']['NAME']='China, Changbai Time';
$aTimeZones['Asia/Harbin']['TZ']='UCT-8';
//  Zhongyuan Time (Central plain Time)
$aTimeZones['Asia/Shanghai']['NAME']='China, Zhongyuan Time';
$aTimeZones['Asia/Shanghai']['TZ']='UCT-8';
//  Long-shu Time
$aTimeZones['Asia/Chongqing']['NAME']='China, Long-shu Time';
$aTimeZones['Asia/Chongqing']['TZ']='UCT-8';
//  Xin-zang Time (Xinjiang-Tibet Time)
$aTimeZones['Asia/Urumqi']['NAME']='China, Xin-zang Time';
$aTimeZones['Asia/Urumqi']['TZ']='UCT-8';
//  Kunlun Time
$aTimeZones['Asia/Kashgar']['NAME']='China, Kunlun Time';
$aTimeZones['Asia/Kashgar']['TZ']='UCT-8';

$aTimeZones['Asia/Hong_Kong']['NAME']='Hong Kong';
$aTimeZones['Asia/Hong_Kong']['TZ']='UCT-8';

$aTimeZones['Asia/Taipei']['NAME']='Taiwan';
$aTimeZones['Asia/Taipei']['TZ']='UCT-8';

$aTimeZones['Asia/Macau']['NAME']='Macau';
$aTimeZones['Asia/Macau']['TZ']='UCT-8';
// EUAsia daylight saving rules
$aTimeZones['Asia/Nicosia']['NAME']='Cyprus';
$aTimeZones['Asia/Nicosia']['TZ']='EET-2EETDST';
$aTimeZones['Europe/Nicosia']['LINK']='Asia/Nicosia'; 

// RussiaAsia daylight saving rules
$aTimeZones['Asia/Tbilisi']['NAME']='Georgia';
$aTimeZones['Asia/Tbilisi']['TZ']='UCT-3';

$aTimeZones['Asia/Dili']['NAME']='East Timor';
$aTimeZones['Asia/Dili']['TZ']='UCT-9';

$aTimeZones['Asia/Calcutta']['NAME']='India';
$aTimeZones['Asia/Calcutta']['TZ']='UCT-5:30';

$aTimeZones['Asia/Jakarta']['NAME']='Indonesia';
$aTimeZones['Asia/Jakarta']['TZ']='UCT-7';
$aTimeZones['Asia/Pontianak']['NAME']='Indonesia, Kalimantan';
$aTimeZones['Asia/Pontianak']['TZ']='UCT-7';
$aTimeZones['Asia/Makassar']['NAME']='Indonesia, Sulavesi';
$aTimeZones['Asia/Makassar']['TZ']='UCT-8';
$aTimeZones['Asia/Jayapura']['NAME']='Indonesia, New Guinea';
$aTimeZones['Asia/Jayapura']['TZ']='UCT-9';

// Persian daylight savings.
$aTimeZones['Asia/Tehran']['NAME']='Iran';
$aTimeZones['Asia/Tehran']['TZ']='UCT-3:30';

// Iraq daylight saving rules
// 1991    max     -       Apr      1      3:00s   1:00    D
// 1991    max     -       Oct      1      3:00s   0       S
$aTimeZones['Asia/Baghdad']['NAME']='Iraq';
$aTimeZones['Asia/Baghdad']['TZ']='IST-3IDT';

// Zion daylight saving rules.
// one of the examples, why politics and religion should be banned 
// from playing with daylight savings
$aTimeZones['Asia/Jerusalem']['NAME']='Israel';
$aTimeZones['Asia/Jerusalem']['TZ']='IST-2IDT';

$aTimeZones['Asia/Tokyo']['NAME']='Japan';
$aTimeZones['Asia/Tokyo']['TZ']='UCT-9'; // JST

// Jordan daylight saving rules
// 1999    max     -       Sep     lastThu 0:00s   0       -
// 2000    max     -       Mar     lastThu 0:00s   1:00    S
$aTimeZones['Asia/Amman']['NAME']='Jordan';
$aTimeZones['Asia/Amman']['TZ']='JST-2JDT';

// Kazakhstan
$aTimeZones['Asia/Almaty']['TZ']='UCT-6';
$aTimeZones['Asia/Qyzylorda']['TZ']='UCT-6';
$aTimeZones['Asia/Aqtobe']['TZ']='UCT-5';
$aTimeZones['Asia/Aqtau']['TZ']='UCT-4';
$aTimeZones['Asia/Oral']['TZ']='UCT-4';

// Kirgiz daylight saving rules
// 1997    max     -       Mar     lastSun 2:30    1:00    S
// 1997    max     -       Oct     lastSun 2:30    0       -
$aTimeZones['Asia/Bishkek']['NAME']='Kyrgyzstan';
$aTimeZones['Asia/Bishkek']['TZ']='UCT-5';

$aTimeZones['Asia/Seoul']['NAME']='Republic of Korea';
$aTimeZones['Asia/Seoul']['TZ']='UCT-9';
$aTimeZones['Asia/Pyongyang']['NAME']='Democratic People\'s Republic of Korea';
$aTimeZones['Asia/Pyongyang']['TZ']='UCT-9';

$aTimeZones['Asia/Kuwait']['NAME']='Kuwait';
$aTimeZones['Asia/Kuwait']['TZ']='UCT-3';

$aTimeZones['Asia/Vientiane']['NAME']='Laos';
$aTimeZones['Asia/Vientiane']['TZ']='UCT-7';

// Lebanon daylight saving rules
// 1993    max     -       Mar     lastSun 0:00    1:00    S
// 1999    max     -       Oct     lastSun 0:00    0       -
$aTimeZones['Asia/Beirut']['NAME']='Lebanon';
$aTimeZones['Asia/Beirut']['TZ']='EUT-2EUTDST';

$aTimeZones['Asia/Kuala_Lumpur']['NAME']='Malaysia';
$aTimeZones['Asia/Kuala_Lumpur']['TZ']='MST-8'; // GMT+8

$aTimeZones['Asia/Kuching']['NAME']='Sabah & Sarawak';
$aTimeZones['Asia/Kuching']['TZ']='MST-8'; // GMT+8

$aTimeZones['Indian/Maldives']['NAME']='Maldives';
$aTimeZones['Indian/Maldives']['TZ']='UCT-5';

// Mongol daylight saving rules
// 2001    max     -       Sep     lastSat 2:00    0       -
// 2002    max     -       Mar     lastSat 2:00    1:00    S
$aTimeZones['Asia/Hovd']['TZ']='EUT-7EUTDST';
$aTimeZones['Asia/Ulaanbaatar']['TZ']='EUT-8EUTDST';
$aTimeZones['Asia/Choibalsan']['TZ']='EUT-9EUTDST';

$aTimeZones['Asia/Katmandu']['NAME']='Nepal';
$aTimeZones['Asia/Katmandu']['TZ']='UCT-5:45';

$aTimeZones['Asia/Muscat']['NAME']='Oman';
$aTimeZones['Asia/Muscat']['TZ']='UCT-4';

$aTimeZones['Asia/Karachi']['NAME']='Pakistan';
$aTimeZones['Asia/Karachi']['TZ']='UCT-5';

// Palestine
// 1999    max     -       Apr     Fri>=15 0:00    1:00    S
// 1999    max     -       Oct     Fri>=15 0:00    0       -
$aTimeZones['Asia/Gaza']['NAME']='Palestine';
$aTimeZones['Asia/Gaza']['TZ']='UCT-2';

$aTimeZones['Asia/Manila']['NAME']='Philippines';
$aTimeZones['Asia/Manila']['TZ']='UCT-8';

$aTimeZones['Asia/Qatar']['NAME']='Qatar';
$aTimeZones['Asia/Qatar']['TZ']='UCT-3';

$aTimeZones['Asia/Riyadh']['NAME']='Saudi Arabia';
$aTimeZones['Asia/Riyadh']['TZ']='UCT-3';

$aTimeZones['Asia/Singapore']['NAME']='Singapore';
$aTimeZones['Asia/Singapore']['TZ']='UCT-8';

$aTimeZones['Asia/Colombo']['NAME']='Sri Lanka';
$aTimeZones['Asia/Colombo']['TZ']='UCT-6';

// Syria daylight saving rules
// 1994    max     -       Oct      1      0:00    0       -
// 1999    max     -       Apr      1      0:00    1:00    S
$aTimeZones['Asia/Damascus']['NAME']='Syria';
$aTimeZones['Asia/Damascus']['TZ']='UCT-2';

$aTimeZones['Asia/Dushanbe']['NAME']='Tajikistan';
$aTimeZones['Asia/Dushanbe']['TZ']='UCT-5';

$aTimeZones['Asia/Bangkok']['NAME']='Thailand';
$aTimeZones['Asia/Bangkok']['TZ']='UCT-7';

$aTimeZones['Asia/Ashgabat']['NAME']='Turkmenistan';
$aTimeZones['Asia/Ashgabat']['TZ']='UCT-5';

$aTimeZones['Asia/Dubai']['NAME']='United Arab Emirates';
$aTimeZones['Asia/Dubai']['TZ']='UCT-4';

// Uzbekistan
$aTimeZones['Asia/Samarkand']['TZ']='UCT-5';
$aTimeZones['Asia/Tashkent']['TZ']='UCT-5';

$aTimeZones['Asia/Saigon']['NAME']='Vietnam';
$aTimeZones['Asia/Saigon']['TZ']='UCT-7';

$aTimeZones['Asia/Aden']['NAME']='Yemen';
$aTimeZones['Asia/Aden']['TZ']='UCT-3';

/** Australia, Oceania, Pacific **/
// Northern Territory, Australia
$aTimeZones['Australia/Darwin']['TZ']='UCT-9:30';
// Western Australia
$aTimeZones['Australia/Perth']['TZ']='UCT-8';
// Queensland
$aTimeZones['Australia/Brisbane']['TZ']='UCT-10';
$aTimeZones['Australia/Lindeman']['TZ']='UCT-10';
// South Australia
// 1987    max     -       Oct     lastSun 2:00s   1:00    -
// 1995    max     -       Mar     lastSun 2:00s   0       -
$aTimeZones['Australia/Adelaide']['TZ']='CST-9:30CDT';
// Tasmania
// 1991    max     -       Mar     lastSun 2:00s   0       -
// 2001    max     -       Oct     Sun>=1  2:00s   1:00    -
$aTimeZones['Australia/Hobart']['TZ']='TST-10TDT';
// Victoria
// 1995    max     -       Mar     lastSun 2:00s   0       -
// 2001    max     -       Oct     lastSun 2:00s   1:00    -
$aTimeZones['Australia/Melbourne']['TZ']='EST-10EDT';
// New South Wales
// 1996    max     -       Mar     lastSun 2:00s   0       -
// 2001    max     -       Oct     lastSun 2:00s   1:00    -
$aTimeZones['Australia/Sydney']['TZ']='EST-10EDT';
$aTimeZones['Australia/Broken_Hill']['TZ']='CST-9:30CDT';
// Lord Howe Island
// 1996    max     -       Mar     lastSun 2:00    0       -
// 2001    max     -       Oct     lastSun 2:00    0:30    -
$aTimeZones['Australia/Lord_Howe']['TZ']='LHT-10:30LHDT';

$aTimeZones['Indian/Christmas']['TZ']='UCT-7';
// Cook Islands
$aTimeZones['Pacific/Rarotonga']['TZ']='UCT10';

$aTimeZones['Indian/Cocos']['TZ']='UCT-6:30';

$aTimeZones['Pacific/Fiji']['TZ']='UCT-12';
// French Polynesia
$aTimeZones['Pacific/Gambier']['TZ']='UCT9';
$aTimeZones['Pacific/Marquesas']['TZ']='UCT9:30';
$aTimeZones['Pacific/Tahiti']['TZ']='UCT10';
// Guam
$aTimeZones['Pacific/Guam']['TZ']='UCT-10';
// Kiribati
$aTimeZones['Pacific/Tarawa']['TZ']='UCT-12';
$aTimeZones['Pacific/Enderbury']['TZ']='UCT-13';
$aTimeZones['Pacific/Kiritimati']['TZ']='UCT-14';
// North Marianas
$aTimeZones['Pacific/Saipan']['TZ']='UCT-10';
// Marshall Islands
$aTimeZones['Pacific/Majuro']['TZ']='UCT-12';
$aTimeZones['Pacific/Kwajalein']['TZ']='UCT-12';
// Micronesia
$aTimeZones['Pacific/Yap']['TZ']='UCT-10';
$aTimeZones['Pacific/Truk']['TZ']='UCT-10';
$aTimeZones['Pacific/Ponape']['TZ']='UCT-11';
$aTimeZones['Pacific/Kosrae']['TZ']='UCT-11';
// Nauru
$aTimeZones['Pacific/Nauru']['TZ']='UCT-12';
// New Caledonia
$aTimeZones['Pacific/Noumea']['TZ']='UCT-11';
// New Zealand
// NZ      1990    max     -       Oct     Sun>=1  2:00s   1:00    D
// Chatham 1990    max     -       Oct     Sun>=1  2:45s   1:00    D
// NZ      1990    max     -       Mar     Sun>=15 2:00s   0       S
// Chatham 1990    max     -       Mar     Sun>=15 2:45s   0       S
$aTimeZones['Pacific/Auckland']['TZ']='NZST-12NZDT';
$aTimeZones['Pacific/Chatham']['TZ']='CIST-12:45CIDT';
// Niue Islands
$aTimeZones['Pacific/Niue']['TZ']='UCT11';
// Norfolk
$aTimeZones['Pacific/Norfolk']['TZ']='UCT-11:30';
// Palau
$aTimeZones['Pacific/Palau']['TZ']='UCT-9';
// Papua New Guinea
$aTimeZones['Pacific/Port_Moresby']['TZ']='UCT-10';
// Pitcairn
$aTimeZones['Pacific/Pitcairn']['TZ']='UCT8';
// American Samoa
$aTimeZones['Pacific/Pago_Pago']['TZ']='UCT11';
// Samoa
$aTimeZones['Pacific/Apia']['TZ']='UCT11';
// Solomon Islands
$aTimeZones['Pacific/Guadalcanal']['TZ']='UCT-11';
// Tokelau Islands
$aTimeZones['Pacific/Fakaofo']['TZ']='UCT10';
// Tonga
$aTimeZones['Pacific/Tongatapu']['TZ']='UCT-13';
// Tuvalu
$aTimeZones['Pacific/Funafuti']['TZ']='UCT-12';
// Johnston
$aTimeZones['Pacific/Johnston']['TZ']='UCT10';
// Midway
$aTimeZones['Pacific/Midway']['TZ']='UCT11';
// Wake
$aTimeZones['Pacific/Wake']['TZ']='UCT-12';
// Vanuatu
$aTimeZones['Pacific/Efate']['TZ']='UCT-11';
// Wallis and Futuna
$aTimeZones['Pacific/Wallis']['TZ']='UCT-12';

/** old timezone names (backward compatibility) **/
$aTimeZones['America/Buenos_Aires']['LINK']='America/Argentina/Buenos_Aires';
$aTimeZones['America/Catamarca']['LINK']='America/Argentina/Catamarca';
$aTimeZones['America/Cordoba']['LINK']='America/Argentina/Cordoba'; 
$aTimeZones['America/Jujuy']['LINK']='America/Argentina/Jujuy';
$aTimeZones['America/Atka']['LINK']='America/Adak';
$aTimeZones['America/Ensenada']['LINK']='America/Tijuana';
$aTimeZones['America/Fort_Wayne']['LINK']='America/Indianapolis';
$aTimeZones['America/Knox_IN']['LINK']='America/Indiana/Knox';
$aTimeZones['America/Mendoza']['LINK']='America/Argentina/Mendoza';
$aTimeZones['America/Porto_Acre']['LINK']='America/Rio_Branco';
$aTimeZones['America/Rosario']['LINK']='America/Argentina/Cordoba';
$aTimeZones['America/Virgin']['LINK']='America/St_Thomas';
$aTimeZones['Asia/Ashkhabad']['LINK']='Asia/Ashgabat';
$aTimeZones['Asia/Chungking']['LINK']='Asia/Chongqing';
$aTimeZones['Asia/Dacca']['LINK']='Asia/Dhaka';
$aTimeZones['Asia/Macao']['LINK']='Asia/Macau';
$aTimeZones['Asia/Ujung_Pandang']['LINK']='Asia/Makassar';
$aTimeZones['Asia/Tel_Aviv']['LINK']='Asia/Jerusalem';
$aTimeZones['Asia/Thimbu']['LINK']='Asia/Thimphu';
$aTimeZones['Asia/Ulan_Bator']['LINK']='Asia/Ulaanbaatar';
$aTimeZones['Australia/ACT']['LINK']='Australia/Sydney';
$aTimeZones['Australia/Canberra']['LINK']='Australia/Sydney';
$aTimeZones['Australia/LHI']['LINK']='Australia/Lord_Howe';
$aTimeZones['Australia/NSW']['LINK']='Australia/Sydney';
$aTimeZones['Australia/North']['LINK']='Australia/Darwin';
$aTimeZones['Australia/Queensland']['LINK']='Australia/Brisbane';
$aTimeZones['Australia/South']['LINK']='Australia/Adelaide';
$aTimeZones['Australia/Tasmania']['LINK']='Australia/Hobart';
$aTimeZones['Australia/Victoria']['LINK']='Australia/Melbourne';
$aTimeZones['Australia/West']['LINK']='Australia/Perth';
$aTimeZones['Australia/Yancowinna']['LINK']='Australia/Broken_Hill';
$aTimeZones['Brazil/Acre']['LINK']='America/Rio_Branco';
$aTimeZones['Brazil/DeNoronha']['LINK']='America/Noronha';
$aTimeZones['Brazil/East']['LINK']='America/Sao_Paulo';
$aTimeZones['Brazil/West']['LINK']='America/Manaus';
$aTimeZones['Canada/Atlantic']['LINK']='America/Halifax';
$aTimeZones['Canada/Central']['LINK']='America/Winnipeg';
$aTimeZones['Canada/East-Saskatchewan']['LINK']='America/Regina';
$aTimeZones['Canada/Eastern']['LINK']='America/Toronto';
$aTimeZones['Canada/Mountain']['LINK']='America/Edmonton';
$aTimeZones['Canada/Newfoundland']['LINK']='America/St_Johns';
$aTimeZones['Canada/Pacific']['LINK']='America/Vancouver';
$aTimeZones['Canada/Saskatchewan']['LINK']='America/Regina';
$aTimeZones['Canada/Yukon']['LINK']='America/Whitehorse';
$aTimeZones['Chile/Continental']['LINK']='America/Santiago';
$aTimeZones['Chile/EasterIsland']['LINK']='Pacific/Easter';
$aTimeZones['Cuba']['LINK']='America/Havana';
$aTimeZones['Egypt']['LINK']='Africa/Cairo';
$aTimeZones['Eire']['LINK']='Europe/Dublin';
$aTimeZones['Europe/Tiraspol']['LINK']='Europe/Chisinau';
$aTimeZones['GB']['LINK']='Europe/London';
$aTimeZones['GB-Eire']['LINK']='Europe/London';
$aTimeZones['GMT+0']['LINK']='Etc/GMT';
$aTimeZones['GMT-0']['LINK']='Etc/GMT';
$aTimeZones['GMT0']['LINK']='Etc/GMT';
$aTimeZones['Greenwich']['LINK']='Etc/GMT';
$aTimeZones['Hongkong']['LINK']='Asia/Hong_Kong';
$aTimeZones['Iceland']['LINK']='Atlantic/Reykjavik';
$aTimeZones['Iran']['LINK']='Asia/Tehran';
$aTimeZones['Israel']['LINK']='Asia/Jerusalem';
$aTimeZones['Jamaica']['LINK']='America/Jamaica';
$aTimeZones['Japan']['LINK']='Asia/Tokyo';
$aTimeZones['Kwajalein']['LINK']='Pacific/Kwajalein';
$aTimeZones['Libya']['LINK']='Africa/Tripoli';
$aTimeZones['Mexico/BajaNorte']['LINK']='America/Tijuana';
$aTimeZones['Mexico/BajaSur']['LINK']='America/Mazatlan';
$aTimeZones['Mexico/General']['LINK']='America/Mexico_City';
$aTimeZones['Navajo']['LINK']='America/Denver';
$aTimeZones['NZ']['LINK']='Pacific/Auckland';
$aTimeZones['NZ-CHAT']['LINK']='Pacific/Chatham';
$aTimeZones['Pacific/Samoa']['LINK']='Pacific/Pago_Pago';
$aTimeZones['Poland']['LINK']='Europe/Warsaw';
$aTimeZones['Portugal']['LINK']='Europe/Lisbon';
$aTimeZones['PRC']['LINK']='Asia/Shanghai';
$aTimeZones['ROC']['LINK']='Asia/Taipei';
$aTimeZones['ROK']['LINK']='Asia/Seoul';
$aTimeZones['Singapore']['LINK']='Asia/Singapore';
$aTimeZones['Turkey']['LINK']='Europe/Istanbul';
$aTimeZones['UCT']['LINK']='Etc/UCT';
$aTimeZones['US/Alaska']['LINK']='America/Anchorage';
$aTimeZones['US/Aleutian']['LINK']='America/Adak';
$aTimeZones['US/Arizona']['LINK']='America/Phoenix';
$aTimeZones['US/Central']['LINK']='America/Chicago';
$aTimeZones['US/East-Indiana']['LINK']='America/Indianapolis';
$aTimeZones['US/Eastern']['LINK']='America/New_York';
$aTimeZones['US/Hawaii']['LINK']='Pacific/Honolulu';
$aTimeZones['US/Indiana-Starke']['LINK']='America/Indiana/Knox';
$aTimeZones['US/Michigan']['LINK']='America/Detroit';
$aTimeZones['US/Mountain']['LINK']='America/Denver';
$aTimeZones['US/Pacific']['LINK']='America/Los_Angeles';
$aTimeZones['US/Samoa']['LINK']='Pacific/Pago_Pago';
$aTimeZones['UTC']['LINK']='Etc/UTC';
$aTimeZones['Universal']['LINK']='Etc/UTC';
$aTimeZones['W-SU']['LINK']='Europe/Moscow';
$aTimeZones['Zulu']['LINK']='Etc/UTC';
// zones that were present in SquirrelMail timezones.cfg and
// not available in GNU C
$aTimeZones['Asia/Ishigaki']['LINK']='Asia/Tokyo';
$aTimeZones['China/Beijing']['LINK']='Asia/Shanghai';
$aTimeZones['China/Shanghai']['LINK']='Asia/Shanghai';
$aTimeZones['GMT']['LINK']='Etc/GMT';
$aTimeZones['Factory']['LINK']='Etc/GMT';

/** etcetera (GMT,UTC, UCT zones) **/
$aTimeZones['Etc/GMT']['TZ']='GMT';
$aTimeZones['Etc/UTC']['TZ']='UTC';
$aTimeZones['Etc/UCT']['TZ']='UCT';

$aTimeZones['Etc/Universal']['LINK']='Etc/UTC';
$aTimeZones['Etc/Zulu']['LINK']='Etc/UTC';
$aTimeZones['Etc/Greenwich']['LINK']='Etc/GMT';
$aTimeZones['Etc/GMT-0']['LINK']='Etc/GMT';
$aTimeZones['Etc/GMT+0']['LINK']='Etc/GMT';
$aTimeZones['Etc/GMT0']['LINK']='Etc/GMT';

$aTimeZones['Etc/GMT-14']['TZ']='GMT-14';
$aTimeZones['Etc/GMT-13']['TZ']='GMT-13';
$aTimeZones['Etc/GMT-12']['TZ']='GMT-12';
$aTimeZones['Etc/GMT-11']['TZ']='GMT-11';
$aTimeZones['Etc/GMT-10']['TZ']='GMT-10';
$aTimeZones['Etc/GMT-9']['TZ']='GMT-9';
$aTimeZones['Etc/GMT-8']['TZ']='GMT-8';
$aTimeZones['Etc/GMT-7']['TZ']='GMT-7';
$aTimeZones['Etc/GMT-6']['TZ']='GMT-6';
$aTimeZones['Etc/GMT-5']['TZ']='GMT-5';
$aTimeZones['Etc/GMT-4']['TZ']='GMT-4';
$aTimeZones['Etc/GMT-3']['TZ']='GMT-3';
$aTimeZones['Etc/GMT-2']['TZ']='GMT-2';
$aTimeZones['Etc/GMT-1']['TZ']='GMT-1';
$aTimeZones['Etc/GMT+1']['TZ']='GMT+1';
$aTimeZones['Etc/GMT+2']['TZ']='GMT+2';
$aTimeZones['Etc/GMT+3']['TZ']='GMT+3';
$aTimeZones['Etc/GMT+4']['TZ']='GMT+4';
$aTimeZones['Etc/GMT+5']['TZ']='GMT+5';
$aTimeZones['Etc/GMT+6']['TZ']='GMT+6';
$aTimeZones['Etc/GMT+7']['TZ']='GMT+7';
$aTimeZones['Etc/GMT+8']['TZ']='GMT+8';
$aTimeZones['Etc/GMT+9']['TZ']='GMT+9';
$aTimeZones['Etc/GMT+10']['TZ']='GMT+10';
$aTimeZones['Etc/GMT+11']['TZ']='GMT+11';
$aTimeZones['Etc/GMT+12']['TZ']='GMT+12';

/** europe **/
// EU daylight saving rules apply unless noted other
// EU      1981    max     -       Mar     lastSun  1:00u  1:00    S
// EU      1996    max     -       Oct     lastSun  1:00u  0       -
// W-Eur   1981    max     -       Mar     lastSun  1:00s  1:00    S
// W-Eur   1996    max     -       Oct     lastSun  1:00s  0       -
// C-Eur   1981    max     -       Mar     lastSun  2:00s  1:00    S
// C-Eur   1996    max     -       Oct     lastSun  2:00s  0       -
// E-Eur   1981    max     -       Mar     lastSun  0:00   1:00    S
// E-Eur   1996    max     -       Oct     lastSun  0:00   0       -
// Russia  1993    max     -       Mar     lastSun  2:00s  1:00    S
// Russia  1996    max     -       Oct     lastSun  2:00s  0       -
// Thule   1993    max     -       Apr     Sun>=1  2:00    1:00    D
// Thule   1993    max     -       Oct     lastSun 2:00    0       S

// Britain (United Kingdom) and Ireland (Eire)
$aTimeZones['Europe/London']['TZ']='GMT';
$aTimeZones['Europe/Belfast']['TZ']='GMT';
$aTimeZones['Europe/Dublin']['TZ']='GMT';
// Old tz names
$aTimeZones['WET']['TZ']='WET0WEST'; // EU (WET/WEST)
$aTimeZones['CET']['TZ']='CET-1CEST'; // C-Eur (CET/CEST)
$aTimeZones['MET']['TZ']='MET-1MEST'; // C-Eur (MET/MEST)
$aTimeZones['EET']['TZ']='EET-2EEST'; // EU (EET/EEST)

$aTimeZones['Europe/Tirane']['NAME']='Albania';
$aTimeZones['Europe/Tirane']['TZ']='MET-1METDST'; // ? CE%sT
$aTimeZones['Europe/Andorra']['NAME']='Andorra';
$aTimeZones['Europe/Andorra']['TZ']='MET-1METDST';
$aTimeZones['Europe/Vienna']['NAME']='Austria';
$aTimeZones['Europe/Vienna']['TZ']='MEZ-1MESZ'; // ? should be CE%sT
$aTimeZones['Europe/Minsk']['NAME']='Belorus'; // Russia daylight saving rules
$aTimeZones['Europe/Minsk']['TZ']='EET-2EETDST';
$aTimeZones['Europe/Brussels']['NAME']='Belgium';
$aTimeZones['Europe/Brussels']['TZ']='MET-1METDST';
$aTimeZones['Europe/Sofia']['NAME']='Bulgaria';
$aTimeZones['Europe/Sofia']['TZ']='EET-2EETDST'; // ? EE%sT
$aTimeZones['Europe/Prague']['NAME']='Czech Republic';
$aTimeZones['Europe/Prague']['TZ']='MET-1METDST';
$aTimeZones['Europe/Copenhagen']['NAME']='Denmark';
$aTimeZones['Europe/Copenhagen']['TZ']='MET-1METDST';
$aTimeZones['Atlantic/Faeroe']['NAME']='Faroe Islands';
$aTimeZones['Atlantic/Faeroe']['TZ']='WET0WETDST';

// Greenland
$aTimeZones['America/Danmarkshavn']['TZ']='GMT'; // no daylight saving rules
$aTimeZones['America/Scoresbysund']['TZ']='EUT1EUTDST';
$aTimeZones['America/Godthab']['TZ']='EUT3EUTDST'; // gmt-3, eu daylight saving rules
$aTimeZones['America/Thule']['TZ']='AST4ADT'; // Thule daylight saving rules

$aTimeZones['Europe/Tallinn']['NAME']='Estonia';
$aTimeZones['Europe/Tallinn']['TZ']='EET-2EETDST';
$aTimeZones['Europe/Helsinki']['NAME']='Finland';
$aTimeZones['Europe/Helsinki']['TZ']='EET-2EETDST';
// Aaland Islands
$aTimeZones['Europe/Mariehamn']['LINK']='Europe/Helsinki';
$aTimeZones['Europe/Paris']['NAME']='France';
$aTimeZones['Europe/Paris']['TZ']='MET-1METDST';
$aTimeZones['Europe/Berlin']['NAME']='Germany';
$aTimeZones['Europe/Berlin']['TZ']='MET-1METDST'; // ? or MEZ-1MESZ
$aTimeZones['Europe/Gibraltar']['NAME']='Gibraltar';
$aTimeZones['Europe/Gibraltar']['TZ']='MET-1METDST';
$aTimeZones['Europe/Athens']['NAME']='Greece';
$aTimeZones['Europe/Athens']['TZ']='EET-2EETDST';
$aTimeZones['Europe/Budapest']['NAME']='Hungary';
$aTimeZones['Europe/Budapest']['TZ']='MET-1METDST';
$aTimeZones['Atlantic/Reykjavik']['NAME']='Iceland';
$aTimeZones['Atlantic/Reykjavik']['TZ']='GMT'; // no daylight saving rules
$aTimeZones['Europe/Rome']['NAME']='Italy';
$aTimeZones['Europe/Rome']['TZ']='MET-1METDST';
$aTimeZones['Europe/Vatican']['LINK']='Europe/Rome';
$aTimeZones['Europe/San_Marino']['LINK']='Europe/Rome';
$aTimeZones['Europe/Riga']['NAME']='Latvia';
$aTimeZones['Europe/Riga']['TZ']='EET-2EETDST';
$aTimeZones['Europe/Vaduz']['NAME']='Liechtenstein';
$aTimeZones['Europe/Vaduz']['TZ']='MET-1METDST';
$aTimeZones['Europe/Vilnius']['NAME']='Lithuania';
$aTimeZones['Europe/Vilnius']['TZ']='EET-2EETDST';
$aTimeZones['Europe/Luxembourg']['NAME']='Luxembourg';
$aTimeZones['Europe/Luxembourg']['TZ']='MET-1METDST';
$aTimeZones['Europe/Malta']['NAME']='Malta';
$aTimeZones['Europe/Malta']['TZ']='MET-1METDST';
$aTimeZones['Europe/Chisinau']['NAME']='Moldova';
$aTimeZones['Europe/Chisinau']['TZ']='EET-2EETDST';
$aTimeZones['Europe/Monaco']['NAME']='Monaco';
$aTimeZones['Europe/Monaco']['TZ']='MET-1METDST';
$aTimeZones['Europe/Amsterdam']['NAME']='Netherlands';
$aTimeZones['Europe/Amsterdam']['TZ']='MET-1METDST';
$aTimeZones['Europe/Oslo']['NAME']='Norway';
$aTimeZones['Europe/Oslo']['TZ']='MET-1METDST';
$aTimeZones['Arctic/Longyearbyen']['LINK']='Europe/Oslo';
$aTimeZones['Atlantic/Jan_Mayen']['LINK']='Europe/Oslo';
$aTimeZones['Europe/Warsaw']['NAME']='Poland';
$aTimeZones['Europe/Warsaw']['TZ']='MET-1METDST';
$aTimeZones['Europe/Lisbon']['NAME']='Portugal';
$aTimeZones['Europe/Lisbon']['TZ']='PWT0PST'; // ? WET0WETDST
$aTimeZones['Atlantic/Azores']['NAME']='Azores';
$aTimeZones['Atlantic/Azores']['TZ']='EUT1EUTDST'; // ? gmt-1 eu daylight saving rules
$aTimeZones['Atlantic/Madeira']['NAME']='Madeira';
$aTimeZones['Atlantic/Madeira']['TZ']='WET0WETDST'; // ?
$aTimeZones['Europe/Bucharest']['NAME']='Romania';
$aTimeZones['Europe/Bucharest']['TZ']='EET-2EETDST';

// Russia (Russia daylight saving rules)
$aTimeZones['Europe/Kaliningrad']['NAME']='Russia, Kaliningrad'; // gmt+2
$aTimeZones['Europe/Kaliningrad']['TZ']='RFT-2RFTDST'; // Russian Fed. Zone 1
$aTimeZones['Europe/Moscow']['NAME']='Russia, Moscow'; // gmt+3
$aTimeZones['Europe/Moscow']['TZ']='RFT-3RFTDST'; // Russian Fed. Zone 2
$aTimeZones['Europe/Samara']['NAME']='Russia, Samara'; // gmt+4
$aTimeZones['Europe/Samara']['TZ']='RFT-4RFTDST'; // Russian Fed. Zone 3
$aTimeZones['Asia/Yekaterinburg']['NAME']='Russia, Yekaterinburg'; // gmt+5
$aTimeZones['Asia/Yekaterinburg']['TZ']='RFT-5RFTDST'; // Russian Fed. Zone 4
$aTimeZones['Asia/Omsk']['NAME']='Russia, Omsk'; // gmt+6
$aTimeZones['Asia/Omsk']['TZ']='RFT-6RFTDST'; // Russian Fed. Zone 5
$aTimeZones['Asia/Novosibirsk']['NAME']='Russia, Novosibirsk'; // gmt+6
$aTimeZones['Asia/Novosibirsk']['TZ']='RFT-6RFTDST'; // Russian Fed. Zone 5
$aTimeZones['Asia/Krasnoyarsk']['NAME']='Russia, Krasnoyarsk'; // gmt+7
$aTimeZones['Asia/Krasnoyarsk']['TZ']='RFT-7RFTDST'; // Russian Fed. Zone 6
$aTimeZones['Asia/Irkutsk']['NAME']='Russia, Irkutsk'; // gmt+8
$aTimeZones['Asia/Irkutsk']['TZ']='RFT-8RFTDST'; // Russian Fed. Zone 7
$aTimeZones['Asia/Yakutsk']['NAME']='Russia, Yakutsk'; // gmt+9
$aTimeZones['Asia/Yakutsk']['TZ']='RFT-9RFTDST'; // Russian Fed. Zone 8
$aTimeZones['Asia/Vladivostok']['NAME']='Russia, Vladivostok'; // gmt+10
$aTimeZones['Asia/Vladivostok']['TZ']='RFT-10RFTDST'; // Russian Fed. Zone 9
$aTimeZones['Asia/Sakhalin']['NAME']='Russia, Sakhalin'; // gmt+10
$aTimeZones['Asia/Sakhalin']['TZ']='RFT-10RFTDST'; // Russian Fed. Zone 9
$aTimeZones['Asia/Magadan']['NAME']='Russia, Magadan'; // gmt+11
$aTimeZones['Asia/Magadan']['TZ']='RFT-11RFTDST'; // Russian Fed. Zone 10
$aTimeZones['Asia/Kamchatka']['NAME']='Russia, Kamchatka'; // gmt+12
$aTimeZones['Asia/Kamchatka']['TZ']='RFT-12RFTDST'; // Russian Fed. Zone 11
$aTimeZones['Asia/Anadyr']['NAME']='Russia, Chukotka'; // gmt+12 Chukotskij avtonomnyj okrug
$aTimeZones['Asia/Anadyr']['TZ']='RFT-12RFTDST'; // Russian Fed. Zone 11

$aTimeZones['Europe/Belgrade']['NAME']='Serbia and Montenegro';
$aTimeZones['Europe/Belgrade']['TZ']='MET-1METDST';

// These independent countries are represented as links to other TZs 
// in GNU C. Use real entries instead of a links in order to have them on menu
$aTimeZones['Europe/Ljubljana']['NAME']='Slovenia';
$aTimeZones['Europe/Ljubljana']['TZ']='MET-1METDST';
$aTimeZones['Europe/Sarajevo']['NAME']='Bosnia and Herzegovina';
$aTimeZones['Europe/Sarajevo']['TZ']='MET-1METDST';
$aTimeZones['Europe/Skopje']['NAME']='Macedonia';
$aTimeZones['Europe/Skopje']['TZ']='MET-1METDST';
$aTimeZones['Europe/Zagreb']['NAME']='Croatia';
$aTimeZones['Europe/Zagreb']['TZ']='MET-1METDST';
$aTimeZones['Europe/Bratislava']['NAME']='Slovakia';
$aTimeZones['Europe/Bratislava']['TZ']='MET-1METDST';

// Spain
$aTimeZones['Europe/Madrid']['NAME']='Spain';
$aTimeZones['Europe/Madrid']['TZ']='MET-1METDST';
$aTimeZones['Africa/Ceuta']['NAME']='Ceuta';
$aTimeZones['Africa/Ceuta']['TZ']='MET-1METDST';
$aTimeZones['Atlantic/Canary']['NAME']='Canary';
$aTimeZones['Atlantic/Canary']['TZ']='WET0WETDST';

$aTimeZones['Europe/Stockholm']['NAME']='Sweden';
$aTimeZones['Europe/Stockholm']['TZ']='MET-1METDST';
$aTimeZones['Europe/Zurich']['NAME']='Switzerland';
$aTimeZones['Europe/Zurich']['TZ']='MET-1METDST';
$aTimeZones['Europe/Istanbul']['NAME']='Turkey';
$aTimeZones['Europe/Istanbul']['TZ']='EET-2EETDST';
$aTimeZones['Asia/Istanbul']['LINK']='Europe/Istanbul';

// Ukraine
$aTimeZones['Europe/Kiev']['NAME']='Ukraine';
$aTimeZones['Europe/Kiev']['TZ']='EET-2EETDST';
$aTimeZones['Europe/Uzhgorod']['NAME']='Ukraine, Ruthenia';
$aTimeZones['Europe/Uzhgorod']['TZ']='EET-2EETDST';
$aTimeZones['Europe/Zaporozhye']['NAME']='Ukraine, Zaporozhye';
$aTimeZones['Europe/Zaporozhye']['TZ']='EET-2EETDST';
$aTimeZones['Europe/Simferopol']['NAME']='Ukraine, Crimea';
$aTimeZones['Europe/Simferopol']['TZ']='EET-2EETDST';

/** northamerica **/
// Rule    US      1967    max     -       Oct     lastSun 2:00    0       S
// Rule    US      1987    max     -       Apr     Sun>=1  2:00    1:00    D
//
$aTimeZones['America/New_York']['NAME']='US Eastern standard time';
$aTimeZones['America/New_York']['TZ']='EST5EDT';
$aTimeZones['America/Chicago']['NAME']='US Central standard time';
$aTimeZones['America/Chicago']['TZ']='CST6CDT';

// Oliver County, ND
$aTimeZones['America/North_Dakota/Center']['NAME']='US, Oliver County [ND]';
$aTimeZones['America/North_Dakota/Center']['TZ']='CST6CDT'; // CST since 1992

$aTimeZones['America/Denver']['NAME']='US Mountain standard time';
$aTimeZones['America/Denver']['TZ']='MST7MDT';
$aTimeZones['America/Los_Angeles']['NAME']='US Pacific standard time';
$aTimeZones['America/Los_Angeles']['TZ']='PST8PDT';

// Aliaska
//$aTimeZones['America/Juneau']['NAME']='US, Juneau [AL]';
$aTimeZones['America/Juneau']['TZ']='NAST9NADT';
//$aTimeZones['America/Yakutat']['NAME']='US, Yakutat [AL]';
$aTimeZones['America/Yakutat']['TZ']='NAST9NADT';
//$aTimeZones['America/Anchorage']['NAME']='US, Anchorage [AL]';
$aTimeZones['America/Anchorage']['TZ']='NAST9NADT';
//$aTimeZones['America/Nome']['NAME']='US, Nome [AL]';
$aTimeZones['America/Nome']['TZ']='NAST9NADT';
// $aTimeZones['America/Adak']['NAME']='US, Aleutian Islands';
$aTimeZones['America/Adak']['TZ']='AST10ADT';

$aTimeZones['Pacific/Honolulu']['NAME']='US, Hawaii';
$aTimeZones['Pacific/Honolulu']['TZ']='UCT10';
$aTimeZones['America/Phoenix']['NAME']='US, Arizona';
$aTimeZones['America/Phoenix']['TZ']='MST7'; // gmt-7
$aTimeZones['America/Shiprock']['LINK']='America/Denver';

$aTimeZones['America/Boise']['NAME']='US, South Idaho';
$aTimeZones['America/Boise']['TZ']='MST7MDT';
$aTimeZones['America/Indianapolis']['NAME']='US, Indiana';
$aTimeZones['America/Indianapolis']['TZ']='EST5';
$aTimeZones['America/Indiana/Indianapolis']['LINK']='America/Indianapolis';
// Crawford County, Indiana
$aTimeZones['America/Indiana/Marengo']['NAME']='US, Crawford County [IN]';
$aTimeZones['America/Indiana/Marengo']['TZ']='EST5';
// Starke County, Indiana
$aTimeZones['America/Indiana/Knox']['NAME']='US, Starke County [IN]';
$aTimeZones['America/Indiana/Knox']['TZ']='EST5';
// Switzerland County, Indiana
$aTimeZones['America/Indiana/Vevay']['NAME']='US, Switzerland County [IN]';
$aTimeZones['America/Indiana/Vevay']['TZ']='EST5';
$aTimeZones['America/Louisville']['NAME']='US, Louisville [KY';
$aTimeZones['America/Louisville']['TZ']='EST5EDT';
$aTimeZones['America/Kentucky/Louisville']['LINK']='America/Louisville';
// Wayne, Clinton, and Russell Counties, Kentucky
$aTimeZones['America/Kentucky/Monticello']['NAME']='US, Wayne, Clinton, and Russell Counties [KY]';
$aTimeZones['America/Kentucky/Monticello']['TZ']='EST5EDT';
// Michigan
$aTimeZones['America/Detroit']['NAME']='US, Michigan';
$aTimeZones['America/Detroit']['TZ']='EST5EDT';
// The Michigan border with Wisconsin switched from EST to CST/CDT in 1973.
$aTimeZones['America/Menominee']['NAME']='US, Menominee [MI]';
$aTimeZones['America/Menominee']['TZ']='CST6CDT';

$aTimeZones['EST5EDT']['LINK']='America/New_York';
$aTimeZones['CST6CDT']['LINK']='America/Chicago';
$aTimeZones['MST7MDT']['LINK']='America/Denver';
$aTimeZones['PST8PDT']['LINK']='America/Los_Angeles';
$aTimeZones['EST']['LINK']='America/Indianapolis';
$aTimeZones['MST']['LINK']='America/Phoenix';
$aTimeZones['HST']['LINK']='Pacific/Honolulu';


// Canada
// Rule    Canada  1974    max     -       Oct     lastSun 2:00    0       S
// Rule    Canada  1987    max     -       Apr     Sun>=1  2:00    1:00    D
// Rule    StJohns 1987    max     -       Oct     lastSun 0:01    0       S
// Rule    StJohns 1989    max     -       Apr     Sun>=1  0:01    1:00    D
$aTimeZones['America/St_Johns']['NAME']='Canada, Newfoundland';
$aTimeZones['America/St_Johns']['TZ']='NST3:30NDT';
$aTimeZones['America/Goose_Bay']['NAME']='Canada, Atlantic';
$aTimeZones['America/Goose_Bay']['TZ']='AST4ADT'; // gmt-4 StJohns daylight savings
//$aTimeZones['America/Halifax']['NAME']='';
$aTimeZones['America/Halifax']['TZ']='AST4ADT'; // gmt-4 Canada daylight savings
//$aTimeZones['America/Glace_Bay']['NAME']='';
$aTimeZones['America/Glace_Bay']['TZ']='AST4ADT'; // gmt-4 Canada daylight savings
// Ontario, Quebec
//$aTimeZones['America/Montreal']['NAME']='';
$aTimeZones['America/Montreal']['TZ']='EST5EDT';
//$aTimeZones['America/Toronto']['NAME']='';
$aTimeZones['America/Toronto']['TZ']='EST5EDT';
//$aTimeZones['America/Thunder_Bay']['NAME']='';
$aTimeZones['America/Thunder_Bay']['TZ']='EST5EDT';
//$aTimeZones['America/Nipigon']['NAME']='';
$aTimeZones['America/Nipigon']['TZ']='EST5EDT';
//$aTimeZones['America/Rainy_River']['NAME']='';
$aTimeZones['America/Rainy_River']['TZ']='CST6CDT';
// Manitoba
// Rule    Winn    1987    max     -       Apr     Sun>=1  2:00    1:00    D
// Rule    Winn    1987    max     -       Oct     lastSun 2:00s   0       S
$aTimeZones['America/Winnipeg']['NAME']='Canada, Manitoba';
$aTimeZones['America/Winnipeg']['TZ']='CST6CDT';
// Saskatchewan
//$aTimeZones['America/Regina']['NAME']='';
$aTimeZones['America/Regina']['TZ']='CST6';
//$aTimeZones['America/Swift_Current']['NAME']='';
$aTimeZones['America/Swift_Current']['TZ']='CST6';
// Alberta
// Rule    Edm     1972    max     -       Oct     lastSun 2:00    0       S
// Rule    Edm     1987    max     -       Apr     Sun>=1  2:00    1:00    D
$aTimeZones['America/Edmonton']['NAME']='Canada, Alberta';
$aTimeZones['America/Edmonton']['TZ']='MST7MDT';
// British Columbia
// Rule    Vanc    1962    max     -       Oct     lastSun 2:00    0       S
// Rule    Vanc    1987    max     -       Apr     Sun>=1  2:00    1:00    D
$aTimeZones['America/Vancouver']['NAME']='Canada, British Columbia';
$aTimeZones['America/Vancouver']['TZ']='PST8PDT';
$aTimeZones['America/Dawson_Creek']['NAME']='Canada, Dawson Creek';
$aTimeZones['America/Dawson_Creek']['TZ']='MST7';
// Northwest Territories, Nunavut, Yukon
// Rule    NT_YK   1980    max     -       Oct     lastSun 2:00    0       S
// Rule    NT_YK   1987    max     -       Apr     Sun>=1  2:00    1:00    D
//$aTimeZones['America/Pangnirtung']['NAME']='';
$aTimeZones['America/Pangnirtung']['TZ']='EST5EDT'; // Canada daylight saving
//$aTimeZones['America/Iqaluit']['NAME']='';
$aTimeZones['America/Iqaluit']['TZ']='EST5EDT'; // Canada daylight saving
//$aTimeZones['America/Rankin_Inlet']['NAME']='';
$aTimeZones['America/Rankin_Inlet']['TZ']='CST6CDT'; // Canada daylight saving
//$aTimeZones['America/Cambridge_Bay']['NAME']='';
$aTimeZones['America/Cambridge_Bay']['TZ']='MST7MDT'; // Canada daylight saving
//$aTimeZones['America/Yellowknife']['NAME']='';
$aTimeZones['America/Yellowknife']['TZ']='MST7MDT';  // NT_YK daylight saving
//$aTimeZones['America/Inuvik']['NAME']='';
$aTimeZones['America/Inuvik']['TZ']='MST7MDT';  // NT_YK daylight saving
//$aTimeZones['America/Whitehorse']['NAME']='';
$aTimeZones['America/Whitehorse']['TZ']='PST8PDT';  // NT_YK daylight saving
//$aTimeZones['America/Dawson']['NAME']='';
$aTimeZones['America/Dawson']['TZ']='PST8PDT';  // NT_YK daylight saving

// Mexico
// Rule    Mexico  2002    max     -       Apr     Sun>=1  2:00    1:00    D
// Rule    Mexico  2002    max     -       Oct     lastSun 2:00    0       S
// Quintana Roo
//$aTimeZones['America/Cancun']['NAME']='';
$aTimeZones['America/Cancun']['TZ']='CST6CDT';
// Campeche, Yucatan
//$aTimeZones['America/Merida']['NAME']='';
$aTimeZones['America/Merida']['TZ']='CST6CDT';
// Coahuila, Durango, Nuevo Leon, Tamaulipas
//$aTimeZones['America/Monterrey']['NAME']='';
$aTimeZones['America/Monterrey']['TZ']='CST6CDT';
// Central Mexico 
//$aTimeZones['America/Mexico_City']['NAME']='';
$aTimeZones['America/Mexico_City']['TZ']='CST6CDT';
// Chihuahua
//$aTimeZones['America/Chihuahua']['NAME']='';
$aTimeZones['America/Chihuahua']['TZ']='MST7MDT';
// Sonora
//$aTimeZones['America/Hermosillo']['NAME']='';
$aTimeZones['America/Hermosillo']['TZ']='MST7';
// Baja California Sur, Nayarit, Sinaloa
//$aTimeZones['America/Mazatlan']['NAME']='';
$aTimeZones['America/Mazatlan']['TZ']='MST7MDT';
// Baja California
//$aTimeZones['America/Tijuana']['NAME']='';
$aTimeZones['America/Tijuana']['TZ']='PST8PDT';

$aTimeZones['America/Anguilla']['NAME']='Anguilla';
$aTimeZones['America/Anguilla']['TZ']='UCT4'; // gmt-4 AST
$aTimeZones['America/Antigua']['NAME']='Antigua';
$aTimeZones['America/Antigua']['TZ']='UCT4';

// Bahamas 
// 1964    max     -       Oct     lastSun 2:00    0       S
// 1987    max     -       Apr     Sun>=1  2:00    1:00    D
$aTimeZones['America/Nassau']['NAME']='Bahamas';
$aTimeZones['America/Nassau']['TZ']='EST5EDT';

$aTimeZones['America/Barbados']['NAME']='Barbados';
$aTimeZones['America/Barbados']['TZ']='UCT4';
$aTimeZones['America/Belize']['NAME']='Belize';
$aTimeZones['America/Belize']['TZ']='UCT6';
$aTimeZones['Atlantic/Bermuda']['NAME']='Bermuda';
$aTimeZones['Atlantic/Bermuda']['TZ']='AST4ADT'; // Bahamas daylight saving rules
$aTimeZones['America/Cayman']['NAME']='Cayman Islands';
$aTimeZones['America/Cayman']['TZ']='EST5';
$aTimeZones['America/Costa_Rica']['NAME']='Costa Rica';
$aTimeZones['America/Costa_Rica']['TZ']='UCT6';
// Cuba
// 2000    max     -       Apr     Sun>=1  0:00s   1:00    D
// 2005    max     -       Oct     lastSun 0:00s   0       S
$aTimeZones['America/Havana']['NAME']='Cuba';
$aTimeZones['America/Havana']['TZ']='UCT5'; // ? C%sT check daylight savings

$aTimeZones['America/Dominica']['NAME']='Dominica';
$aTimeZones['America/Dominica']['TZ']='UCT4'; // AST4
$aTimeZones['America/Santo_Domingo']['NAME']='Dominican Republic';
$aTimeZones['America/Santo_Domingo']['TZ']='UCT4';
$aTimeZones['America/El_Salvador']['NAME']='El_Salvador';
$aTimeZones['America/El_Salvador']['TZ']='UCT6';
$aTimeZones['America/Grenada']['NAME']='Grenada';
$aTimeZones['America/Grenada']['TZ']='UCT4';
$aTimeZones['America/Guadeloupe']['NAME']='Guadeloupe';
$aTimeZones['America/Guadeloupe']['TZ']='UCT4';
$aTimeZones['America/Guatemala']['NAME']='Guatemala';
$aTimeZones['America/Guatemala']['TZ']='UCT6';
$aTimeZones['America/Port-au-Prince']['NAME']='Haiti';
$aTimeZones['America/Port-au-Prince']['TZ']='EST5EDT';
$aTimeZones['America/Tegucigalpa']['NAME']='Honduras';
$aTimeZones['America/Tegucigalpa']['TZ']='UCT6';
$aTimeZones['America/Jamaica']['NAME']='Jamaica';
$aTimeZones['America/Jamaica']['TZ']='EST5';
$aTimeZones['America/Martinique']['NAME']='Martinique';
$aTimeZones['America/Martinique']['TZ']='UCT4'; // AST4
$aTimeZones['America/Montserrat']['NAME']='Montserrat';
$aTimeZones['America/Montserrat']['TZ']='UCT4';
// Nicaragua
// Rule    Nic     2005    only    -       Apr     10      0:00    1:00    D
// Rule    Nic     2005    only    -       Sep     11      0:00    0       S
$aTimeZones['America/Managua']['NAME']='Nicaragua';
$aTimeZones['America/Managua']['TZ']='CST6CDT';
$aTimeZones['America/Panama']['NAME']='Panama';
$aTimeZones['America/Panama']['TZ']='EST5';
$aTimeZones['America/Puerto_Rico']['NAME']='Puerto Rico';
$aTimeZones['America/Puerto_Rico']['TZ']='UCT4'; // AST4
$aTimeZones['America/St_Kitts']['NAME']='St Kitts-Nevis';
$aTimeZones['America/St_Kitts']['TZ']='UCT4'; // AST4
$aTimeZones['America/St_Lucia']['NAME']='St Lucia';
$aTimeZones['America/St_Lucia']['TZ']='UCT4'; // AST4
$aTimeZones['America/Miquelon']['NAME']='St Pierre and Miquelon';
$aTimeZones['America/Miquelon']['TZ']='UCT3'; // gmt-3 Canada daylight saving rules PMST3PMDT
$aTimeZones['America/St_Vincent']['NAME']='St Vincent and the Grenadines';
$aTimeZones['America/St_Vincent']['TZ']='UCT4'; // AST4

// Rule    TC      1979    max     -       Oct     lastSun 0:00    0       S
// Rule    TC      1987    max     -       Apr     Sun>=1  0:00    1:00    D
$aTimeZones['America/Grand_Turk']['NAME']='Turks and Caicos';
$aTimeZones['America/Grand_Turk']['TZ']='EST5EDT';

$aTimeZones['America/Tortola']['NAME']='British Virgin Islands';
$aTimeZones['America/Tortola']['TZ']='UCT4'; // AST4
$aTimeZones['America/St_Thomas']['NAME']='Virgin Islands';
$aTimeZones['America/St_Thomas']['TZ']='UCT4'; // AST4

// Pacific Presidential Election Time
$aTimeZones['US/Pacific-New']['LINK']='America/Los_Angeles';

/** southamerica **/

// Argentina
// Buenos Aires (BA), Capital Federal (CF)
//$aTimeZones['America/Argentina/Buenos_Aires']['NAME']='';
$aTimeZones['America/Argentina/Buenos_Aires']['TZ']='SAT3'; // gmt-3 2000
// Santa Fe (SF), Entre Rios (ER), Corrientes (CN), Misiones (MN), Chaco (CC),
// Formosa (FM), Salta (SA), Santiago del Estero (SE), Cordoba (CB),
// San Luis (SL), La Pampa (LP), Neuquen (NQ), Rio Negro (RN)
//$aTimeZones['America/Argentina/Cordoba']['NAME']='';
$aTimeZones['America/Argentina/Cordoba']['TZ']='SAT3'; // gmt-3 since 2000
// Tucuman (TM)
//$aTimeZones['America/Argentina/Tucuman']['NAME']='';
$aTimeZones['America/Argentina/Tucuman']['TZ']='SAT3'; // gmt-3 since 2004
// La Rioja (LR)
//$aTimeZones['America/Argentina/La_Rioja']['NAME']='';
$aTimeZones['America/Argentina/La_Rioja']['TZ']='SAT3'; // gmt-3 since 2004
// San Juan (SJ)
//$aTimeZones['America/Argentina/San_Juan']['NAME']='';
$aTimeZones['America/Argentina/San_Juan']['TZ']='SAT3'; // gmt-3 since 2004
// Jujuy (JY)
//$aTimeZones['America/Argentina/Jujuy']['NAME']='';
$aTimeZones['America/Argentina/Jujuy']['TZ']='SAT3'; // gmt-3 since 2000
// Catamarca (CT)
//$aTimeZones['America/Argentina/Catamarca']['NAME']='';
$aTimeZones['America/Argentina/Catamarca']['TZ']='SAT3'; // gmt-3 since 2004
// Mendoza (MZ)
//$aTimeZones['America/Argentina/Mendoza']['NAME']='';
$aTimeZones['America/Argentina/Mendoza']['TZ']='SAT3'; // gmt-3 since 2004
// Chubut (CH)
//$aTimeZones['America/Argentina/ComodRivadavia']['NAME']='';
$aTimeZones['America/Argentina/ComodRivadavia']['TZ']='SAT3'; // gmt-3 since 2004
// Santa Cruz (SC)
// $aTimeZones['America/Argentina/Rio_Gallegos']['NAME']='';
$aTimeZones['America/Argentina/Rio_Gallegos']['TZ']='SAT3'; // gmt-3 since 2004
// Tierra del Fuego, Antartida e Islas del Atlantico Sur (TF)
//$aTimeZones['America/Argentina/Ushuaia']['NAME']='';
$aTimeZones['America/Argentina/Ushuaia']['TZ']='SAT3'; // gmt-3 since 2004

$aTimeZones['America/Aruba']['NAME']='Aruba';
$aTimeZones['America/Aruba']['TZ']='UCT4'; // AST4
$aTimeZones['America/La_Paz']['NAME']='Bolivia';
$aTimeZones['America/La_Paz']['TZ']='UCT4'; // BOT4

// Brazil
// 2001    max     -       Feb     Sun>=15  0:00   0       -
// 2004    only    -       Nov      2       0:00   1:00    S
// 2005    max     -       Oct     Sun>=15  0:00   1:00    S
// Fernando de Noronha (administratively part of PE)
// $aTimeZones['America/Noronha']['NAME']='';
$aTimeZones['America/Noronha']['TZ']='NORO2';
// Amapa (AP), east Para (PA)
//$aTimeZones['America/Belem']['NAME']='';
$aTimeZones['America/Belem']['TZ']='BRT3'; // gmt-3
//  Maranhao (MA), Piaui (PI), Ceara (CE), Rio Grande do Norte (RN), Paraiba (PB)
//$aTimeZones['America/Fortaleza']['NAME']='';
$aTimeZones['America/Fortaleza']['TZ']='BRT3'; // gmt-3
// Pernambuco (PE) (except Atlantic islands)
//$aTimeZones['America/Recife']['NAME']='';
$aTimeZones['America/Recife']['TZ']='BRT3'; // gmt-3
// Tocantins (TO)
//$aTimeZones['America/Araguaina']['NAME']='';
$aTimeZones['America/Araguaina']['TZ']='BRT3';
// Alagoas (AL), Sergipe (SE)
//$aTimeZones['America/Maceio']['NAME']='';
$aTimeZones['America/Maceio']['TZ']='BRT3';
// Bahia (BA)
//$aTimeZones['America/Bahia']['NAME']='';
$aTimeZones['America/Bahia']['TZ']='BRT3';
// Goias (GO), Distrito Federal (DF), Minas Gerais (MG),
// Espirito Santo (ES), Rio de Janeiro (RJ), Sao Paulo (SP), Parana (PR),
// Santa Catarina (SC), Rio Grande do Sul (RS)
//$aTimeZones['America/Sao_Paulo']['NAME']='';
$aTimeZones['America/Sao_Paulo']['TZ']='BRT3BRST'; // ? gmt-3 Brasil daylight saving rules
// Mato Grosso do Sul (MS)
//$aTimeZones['America/Campo_Grande']['NAME']='';
$aTimeZones['America/Campo_Grande']['TZ']='AMT4AMST'; // ? gmt-4 Brasil daylight saving rules
// Mato Grosso (MT)
//$aTimeZones['America/Cuiaba']['NAME']='';
$aTimeZones['America/Cuiaba']['TZ']='AMT4AMST'; // ? gmt-4 Brasil daylight saving rules
// west Para (PA), Rondonia (RO)
//$aTimeZones['America/Porto_Velho']['NAME']='';
$aTimeZones['America/Porto_Velho']['TZ']='AMT4'; // gmt-4
// Roraima (RR)
//$aTimeZones['America/Boa_Vista']['NAME']='';
$aTimeZones['America/Boa_Vista']['TZ']='AMT4'; // gmt-4
// east Amazonas (AM): Boca do Acre, Jutai, Manaus, Floriano Peixoto
//$aTimeZones['America/Manaus']['NAME']='';
$aTimeZones['America/Manaus']['TZ']='AMT4';
// west Amazonas (AM): Atalaia do Norte, Boca do Maoco, Benjamin Constant,
// Eirunepe, Envira, Ipixuna
//$aTimeZones['America/Eirunepe']['NAME']='';
$aTimeZones['America/Eirunepe']['TZ']='ACT5';
// Acre (AC)
//$aTimeZones['America/Rio_Branco']['NAME']='';
$aTimeZones['America/Rio_Branco']['TZ']='ACT5';

// Chile
//Rule    Chile   1999    max     -       Oct     Sun>=9  4:00u   1:00    S
//Rule    Chile   2000    max     -       Mar     Sun>=9  3:00u   0       -
$aTimeZones['America/Santiago']['NAME']='Chile';
$aTimeZones['America/Santiago']['TZ']='CST4CDT';
$aTimeZones['Pacific/Easter']['NAME']='Chile, Easter Island';
$aTimeZones['Pacific/Easter']['TZ']='EIST6EIDT';

$aTimeZones['America/Bogota']['NAME']='Colombia';
$aTimeZones['America/Bogota']['TZ']='UCT5'; // COT5
$aTimeZones['America/Curacao']['NAME']='Curacao';
$aTimeZones['America/Curacao']['TZ']='UCT4'; // AST4
$aTimeZones['America/Guayaquil']['NAME']='Equador';
$aTimeZones['America/Guayaquil']['TZ']='UCT5'; // ECT5
$aTimeZones['Pacific/Galapagos']['NAME']='Equador, Galapagos';
$aTimeZones['Pacific/Galapagos']['TZ']='UCT6'; // GALT6

// Falklands
//Rule    Falk    2001    max     -       Apr     Sun>=15 2:00    0       -
//Rule    Falk    2001    max     -       Sep     Sun>=1  2:00    1:00    S
$aTimeZones['Atlantic/Stanley']['NAME']='Falklands';
$aTimeZones['Atlantic/Stanley']['TZ']='FKT4FKST';

$aTimeZones['America/Cayenne']['NAME']='French Guiana';
$aTimeZones['America/Cayenne']['TZ']='SAT3';
$aTimeZones['America/Guyana']['NAME']='Guyana';
$aTimeZones['America/Guyana']['TZ']='UCT4';
// Paraguay
// Rule    Para    2004    max     -       Oct     Sun>=15 0:00    1:00    S
// Rule    Para    2005    max     -       Mar     Sun>=8  0:00    0       -
$aTimeZones['America/Asuncion']['NAME']='Paraguay';
$aTimeZones['America/Asuncion']['TZ']='PYT4PYST';

$aTimeZones['America/Lima']['NAME']='Peru';
$aTimeZones['America/Lima']['TZ']='PET5';
$aTimeZones['Atlantic/South_Georgia']['NAME']='South Georgia';
$aTimeZones['Atlantic/South_Georgia']['TZ']='UCT2'; // gmt-2 GST2
$aTimeZones['America/Paramaribo']['NAME']='Suriname';
$aTimeZones['America/Paramaribo']['TZ']='UCT3'; // gmt-3 SRT3
$aTimeZones['America/Port_of_Spain']['NAME']='Trinidad and Tobago';
$aTimeZones['America/Port_of_Spain']['TZ']='UCT4'; // AST4

// Uruguay
//Rule    Uruguay 2004    only    -       Sep     19       0:00   1:00    S
//Rule    Uruguay 2005    only    -       Mar     27       2:00   0       -
$aTimeZones['America/Montevideo']['NAME']='Uruguay';
$aTimeZones['America/Montevideo']['TZ']='SAT3'; // ?

$aTimeZones['America/Caracas']['NAME']='Venezuela';
$aTimeZones['America/Caracas']['TZ']='UCT4'; // VET4

/** SystemV **/
$aTimeZones['SystemV/AST4ADT']['LINK']='America/Halifax';
$aTimeZones['SystemV/EST5EDT']['LINK']='America/New_York';
$aTimeZones['SystemV/CST6CDT']['LINK']='America/Chicago';
$aTimeZones['SystemV/MST7MDT']['LINK']='America/Denver';
$aTimeZones['SystemV/PST8PDT']['LINK']='America/Los_Angeles';
$aTimeZones['SystemV/YST9YDT']['LINK']='America/Anchorage';
$aTimeZones['SystemV/AST4']['LINK']='America/Puerto_Rico';
$aTimeZones['SystemV/EST5']['LINK']='America/Indianapolis';
$aTimeZones['SystemV/CST6']['LINK']='America/Regina';
$aTimeZones['SystemV/MST7']['LINK']='America/Phoenix';
$aTimeZones['SystemV/PST8']['LINK']='Pacific/Pitcairn';
$aTimeZones['SystemV/YST9']['LINK']='Pacific/Gambier';
$aTimeZones['SystemV/HST10']['LINK']='Pacific/Honolulu';

// Saudi Arabia (solar87/solar88/solar89)
//$aTimeZones['Asia/Riyadh87']['NAME']='';
$aTimeZones['Asia/Riyadh87']['TZ']='UCT-3:07:04';
$aTimeZones['Mideast/Riyadh87']['LINK']='Asia/Riyadh87';
//$aTimeZones['Asia/Riyadh88']['NAME']='';
$aTimeZones['Asia/Riyadh88']['TZ']='UCT-3:07:04';
$aTimeZones['Mideast/Riyadh88']['LINK']='Asia/Riyadh88';
//$aTimeZones['Asia/Riyadh89']['NAME']='';
$aTimeZones['Asia/Riyadh89']['TZ']='UCT-3:07:04';
$aTimeZones['Mideast/Riyadh89']['LINK']='Asia/Riyadh89';

