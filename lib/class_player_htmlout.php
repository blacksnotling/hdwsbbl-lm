<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2009-2011. All Rights Reserved.
 *
 *
 *  This file is part of OBBLM.
 *
 *  OBBLM is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  OBBLM is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/*********************
*   Roster/status colors
*********************/
define('COLOR_HTML_NORMAL',   '#FFFFFF'); // Color used when not in detailed view mode.
define('COLOR_HTML_READY',    '#83b783');
define('COLOR_HTML_MNG',      '#6495ED');
define('COLOR_HTML_DEAD',     '#F78771');
define('COLOR_HTML_SOLD',     '#D2B477');
define('COLOR_HTML_STARMERC', '#bb99bb');
define('COLOR_HTML_JOURNEY',  '#99BBBB');
define('COLOR_HTML_JOURNEY_USED', '#FF99FF');
define('COLOR_HTML_NEWSKILL', '#BBBBBB');
//-----
define('COLOR_HTML_CHR_EQP1', '#B3F0B3'); // Characteristic equal plus one.
define('COLOR_HTML_CHR_GTP1', '#50FF50'); // Characteristic greater than plus one.
define('COLOR_HTML_CHR_EQM1', '#FF8888'); // Characteristic equal minus one.
define('COLOR_HTML_CHR_LTM1', '#FF4444'); // Characteristic less than minus one.
define('COLOR_HTML_CHR_BROKENLIMIT', '#FF9900'); // Characteristic less than minus one.

class Player_HTMLOUT extends Player
{

public static function standings()
{
    global $lng;
    title($lng->getTrn('menu/statistics_menu/player_stn'));
    HTMLOUT::standings(STATS_PLAYER,false,false,array('url' => urlcompile(T_URL_STANDINGS,T_OBJ_PLAYER,false,false,false)));
}

public static function profile($pid)
{
    if ($pid < 0)
        fatal('Sorry, star players to do have regular player profiles.');

    global $lng, $coach, $settings;
    $p = new self($pid);
    $team = new Team($p->owned_by_team_id);

    /* Argument(s) passed to generating functions. */
    $ALLOW_EDIT = (is_object($coach) && ($team->owned_by_coach_id == $coach->coach_id || $coach->isNodeCommish(T_NODE_LEAGUE, $team->f_lid)) && !$team->is_retired);

    /* Player pages consist of the output of these generating functions. */
    $p->_handleActions($ALLOW_EDIT); # Handles any actions/request sent.
    $p->_head($team);
    $p->_about($ALLOW_EDIT);
    $p->_achievements();
    $p->_matchBest();
    $p->_recentGames();
    $p->_injuryHistory();
    if (!$settings['hide_ES_extensions']) {
        $p->_ES();
    }
}

public static function setChoosableSkillsTranslations($player) {
    global $skillididx, $CHR_CONV;

    $player->choosable_skills_strings = array();

    foreach($player->choosable_skills["norm"] as $skillId) {
        $player->choosable_skills_strings["norm"][$skillId] = $skillididx[$skillId];
    }

    foreach($player->choosable_skills["doub"] as $skillId) {
        $player->choosable_skills_strings["doub"][$skillId] = $skillididx[$skillId];
    }

    foreach($player->choosable_skills["chr"] as $skillId) {
        $player->choosable_skills_strings["chr"][$skillId] = ucfirst($CHR_CONV[$skillId]);
    }
}

private function _handleActions($ALLOW_EDIT)
{
    $p = $this; // Copy. Used instead of $this for readability.

    if (!$ALLOW_EDIT || !isset($_POST['type'])) {
        return false;
    }

    switch ($_POST['type'])
    {
        case 'pic':
            status(($_POST['add_del'] == 'add') ? $p->savePic(false) : $p->deletePic());
            break;

        case 'playertext':
            if (get_magic_quotes_gpc()) {
                $_POST['playertext'] = stripslashes($_POST['playertext']);
            }
            status($p->saveText($_POST['playertext']));
            break;
    }
}

private function _head($team)
{
    global $lng;
    $p = $this; // Copy. Used instead of $this for readability.

    title($p->name);
    $players = $team->getPlayers();
    $i = $next = $prev = 0;
    $keys = array_keys($players);
    $end = end($keys);
    foreach ($players as $player) {
        if ($player->player_id == $p->player_id) {
            if ($i == 0) {
                $prev = $end;
                $next = 1;
            }
            elseif ($i == $end) {
                $prev = $end - 1;
                $next = 0;
            }
            else {
                $prev = $i-1;
                $next = $i+1;
            }
        }
        $i++;
    }
    if (count($players) > 1) {
        echo "<center><a href='".urlcompile(T_URL_PROFILE,T_OBJ_PLAYER,$players[$prev]->player_id,false,false)."'>".$lng->getTrn('common/previous')."</a> &nbsp;|&nbsp; <a href='".urlcompile(T_URL_PROFILE,T_OBJ_PLAYER,$players[$next]->player_id,false,false)."'>".$lng->getTrn('common/next')."</a></center><br>";
    }
}

private function _about($ALLOW_EDIT)
{
    global $lng;
    $p = $this; // Copy. Used instead of $this for readability.

    $p->skills = $p->getSkillsStr(true);
    $p->injs = $p->getInjsStr(true);

    ?>
    <div class="row">
        <div class="boxPlayerPageInfo">
            <div class="boxTitle<?php echo T_HTMLBOX_INFO;?>"><?php echo $lng->getTrn('profile/player/about');?></div>
            <div class="boxBody">
                <table class="pbox">
                    <tr>
                        <td><strong><?php echo $lng->getTrn('common/name');?></strong></td>
                        <td><?php echo "$p->name (#$p->nr)"; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo $lng->getTrn('common/pos');?></strong></td>
                        <td><?php echo $lng->getTrn('position/'.strtolower($lng->FilterPosition($p->position))); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo $lng->getTrn('common/team');?></strong></td>
                        <td><a href="<?php echo urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$p->owned_by_team_id,false,false);?>"><?php echo $p->f_tname; ?></a></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo $lng->getTrn('common/bought');?></strong></td>
                        <td><?php echo $p->date_bought; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo $lng->getTrn('common/status');?></strong></td>
                        <td>
                        <?php
                            if ($p->is_dead) {
                                echo "<strong><font color='red'>".$lng->getTrn('common/dead')."</font></strong> ($p->date_died)";
                            }
                            elseif ($p->is_sold) {
                                echo "<strong>".$lng->getTrn('common/sold')."</strong> ($p->date_sold)";
                            }
                            else {
                                global $T_INJS;
                                $status = ucfirst(strtolower($T_INJS[$p->status]));
                                echo ($status == 'none') ? '<strong><font color="green">'.$lng->getTrn('common/ready').'</font></strong>' : "<strong><font color='blue'>$status</font></strong>";
                            }
                        ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php echo $lng->getTrn('common/value');?></strong></td>
                        <td><?php echo $p->value/1000 .'k' ?></td>
                    </tr>
                    <tr>
                        <td><strong>SPP/extra</strong></td>
                        <td><?php echo "$p->mv_spp/$p->extra_spp" ?></td>
                    </tr>
                    <?php
                    if (Module::isRegistered('Wanted')) {
                        ?>
                        <tr>
                            <td><strong>Wanted</strong></td>
                            <td><?php echo (Module::run('Wanted', array('isWanted', $p->player_id))) ? '<strong><font color="red">Yes</font></strong>' : 'No';?></td>
                        </tr>
                        <?php
                    }
                    if (Module::isRegistered('HOF')) {
                        ?>
                        <tr>
                            <td><strong>In HoF</strong></td>
                            <td><?php echo (Module::run('HOF', array('isInHOF', $p->player_id))) ? '<strong><font color="green">Yes</font></strong>' : 'No';?></td>
                        </tr>
                        <?php
                    }
                    ?>
                    <tr>
                        <td><strong><?php echo $lng->getTrn('common/played');?></strong></td>
                        <td><?php echo $p->mv_played;?></td>
                    </tr>
                    <tr>
                        <td><strong>W/L/D</strong></td>
                        <td><?php echo "$p->mv_won/$p->mv_lost/$p->mv_draw"; ?></td>
                    </tr>
                    <?php
                    if (Module::isRegistered('SGraph')) {
                        ?>
                        <tr>
                            <td><strong>Vis. stats</strong></td>
                            <td><?php echo "<a href='handler.php?type=graph&amp;gtype=".SG_T_PLAYER."&amp;id=$p->player_id''>".$lng->getTrn('common/view')."</a>\n";?></td>
                        </tr>
                        <?php
                    }
                    ?>
                    <tr>
                        <td colspan="2"><hr></td>
                    </tr>
                    <tr>
                        <td><strong>Ma</strong></td>
                        <td><?php echo $p->ma; ?></td>
                    </tr>
                    <tr>
                        <td><strong>St</strong></td>
                        <td><?php echo $p->st; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Ag</strong></td>
                        <td><?php echo $p->ag; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Av</strong></td>
                        <td><?php echo $p->av; ?></td>
                    </tr>
                    <tr valign="top">
                        <td><strong><?php echo $lng->getTrn('common/skills');?></strong></td>
                        <td><?php echo (empty($p->skills)) ? '<em>'.$lng->getTrn('common/none').'</i>' : $p->skills; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo $lng->getTrn('common/injs');?></strong></td>
                        <td><?php echo (empty($p->injs)) ? '<em>'.$lng->getTrn('common/none').'</i>' : $p->injs; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Cp</strong></td>
                        <td><?php echo $p->mv_cp; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Td</strong></td>
                        <td><?php echo $p->mv_td; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Int</strong></td>
                        <td><?php echo $p->mv_intcpt; ?></td>
                    </tr>
                    <tr>
                        <td><strong>BH/SI/Ki</strong></td>
                        <td><?php echo "$p->mv_bh/$p->mv_si/$p->mv_ki"; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Cas</strong></td>
                        <td><?php echo $p->mv_cas; ?></td>
                    </tr>
                    <tr>
                        <td><strong>MVP</strong></td>
                        <td><?php echo $p->mv_mvp; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="boxCommon">
            <div class="boxTitle<?php echo T_HTMLBOX_INFO;?>"><?php echo $lng->getTrn('profile/player/profile');?></div>
            <div class="boxBody">
                <em><?php echo $lng->getTrn('common/picof');?></i><hr>
                <?php
                ImageSubSys::makeBox(IMGTYPE_PLAYER, $p->player_id, $ALLOW_EDIT, false);
                ?>
                <br><br>
                <em><?php echo $lng->getTrn('common/about');?></i><hr>
                <?php
                $txt = $p->getText();
                if (empty($txt)) {
                    $txt = $lng->getTrn('common/nobody');
                }
                if ($ALLOW_EDIT) {
                    ?>
                    <form method="POST" enctype="multipart/form-data">
                        <textarea name='playertext' rows='8' cols='45'><?php echo $txt;?></textarea>
                        <br><br>
                        <input type="hidden" name="type" value="playertext">
                        <input type="submit" name='Save' value='<?php echo $lng->getTrn('common/save');?>'>
                    </form>
                    <?php
                }
                else {
                    echo '<p>'.fmtprint($txt).'</p>';
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}

private function _achievements()
{
    global $lng;
    $p = $this; // Copy. Used instead of $this for readability.

    ?>
    <div class="row">
        <div class="boxWide">
            <div class="boxTitle<?php echo T_HTMLBOX_STATS;?>"><a href='javascript:void(0);' onClick="slideToggleFast('ach');"><strong>[+/-]</strong></a> &nbsp;<?php echo $lng->getTrn('common/ach');?></div>
            <div class="boxBody" id="ach" style='display:none;'>
                <table class="common">
                    <tr>
                        <td><strong><?php echo $lng->getTrn('common/type');?></strong></td>
                        <td><strong><?php echo $lng->getTrn('common/tournament');?></strong></td>
                        <td><strong><?php echo $lng->getTrn('common/opponent');?></strong></td>
                        <td><strong>MVP</strong></td>
                        <td><strong>Cp</strong></td>
                        <td><strong>Td</strong></td>
                        <td><strong>Int</strong></td>
                        <td><strong>Cas</strong></td>
                        <td><strong><?php echo $lng->getTrn('common/score');?></strong></td>
                        <td><strong><?php echo $lng->getTrn('common/result');?></strong></td>
                        <td><strong><?php echo $lng->getTrn('common/match');?></strong></td>
                    </tr>
                    <?php
                    foreach (array('intcpt' => 'Interceptions', 'cp' => 'Completions', 'td' => 'Touchdowns', 'mvp' => 'MVP awards', 'bh+ki+si' => 'Cas') as $s => $desc) {
                        $been_there = false;
                        foreach ($p->getAchEntries($s) as $entry) {
                            if (!$been_there)
                                echo "<tr><td colspan='11'><hr></td></tr>";
                            ?>
                            <tr>
                                <?php
                                $m = $entry['match_obj'];
                                if ($been_there) {
                                    echo '<td></td>';
                                }
                                else {
                                    echo "<td><em>$desc: " . (($desc == 'Cas') ? $p->{"mv_cas"} : $p->{"mv_$s"}) . "</i></td>";
                                    $been_there = true;
                                }
                                ?>
                                <td><?php echo get_parent_name(T_NODE_MATCH, $m->match_id, T_NODE_TOURNAMENT);?></td>
                                <td><?php echo ($p->owned_by_team_id == $m->team1_id) ? $m->team2_name : $m->team1_name; ?></td>
                                <td><?php echo $entry['mvp']; ?></td>
                                <td><?php echo $entry['cp']; ?></td>
                                <td><?php echo $entry['td']; ?></td>
                                <td><?php echo $entry['intcpt']; ?></td>
                                <td><?php echo $entry['bh']+$entry['si']+$entry['ki']; ?></td>
                                <td><?php echo $m->team1_score .' - '. $m->team2_score; ?></td>
                                <td><?php echo matchresult_icon((($m->is_draw) ? 'D' : (($m->winner == $p->owned_by_team_id) ? 'W' : 'L'))); ?></td>
                                <td><a href='javascript:void(0)' onClick="window.open('index.php?section=matches&amp;type=report&amp;mid=<?php echo $m->match_id;?>');"><?php echo $lng->getTrn('common/view');?></a></td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>
    <?php
}

private function _matchBest()
{
    global $lng;
    $p = $this; // Copy. Used instead of $this for readability.

    ?>
    <div class="row">
        <div class="boxWide">
            <div class="boxTitle<?php echo T_HTMLBOX_STATS;?>"><a href='javascript:void(0);' onClick="slideToggleFast('mbest');"><strong>[+/-]</strong></a> &nbsp;<?php echo $lng->getTrn('profile/player/best');?></div>
            <div class="boxBody" id="mbest">
                <table class="common">
                    <tr>
                        <td><strong><?php echo $lng->getTrn('common/type');?></strong></td>
                        <td><strong><?php echo $lng->getTrn('common/tournament');?></strong></td>
                        <td><strong><?php echo $lng->getTrn('common/opponent');?></strong></td>
                        <td><strong>Td</strong></td>
                        <td><strong>Ki</strong></td>
                        <td><strong><?php echo $lng->getTrn('common/score');?></strong></td>
                        <td><strong><?php echo $lng->getTrn('common/result');?></strong></td>
                        <td><strong><?php echo $lng->getTrn('common/match');?></strong></td>
                    </tr>
                    <?php
                    foreach (array('td' => 'scorer', 'ki' => 'killer') as $s => $desc) {
                        $been_there = false;
                        $matches = $p->getMatchMost($s);
                        foreach ($matches as $entry) {
                            if (!$been_there)
                                echo "<tr><td colspan='8'><hr></td></tr>";
                            ?>
                            <tr>
                                <?php
                                $m = $entry['match_obj'];
                                if ($been_there) {
                                    echo '<td></td>';
                                }
                                else {
                                    echo "<td><em>Top $desc: " . count($matches) . " times</i></td>";
                                    $been_there = true;
                                }
                                ?>
                                <td><?php echo get_alt_col('tours', 'tour_id', $m->f_tour_id, 'name'); ?></td>
                                <td><?php echo ($p->owned_by_team_id == $m->team1_id) ? $m->team2_name : $m->team1_name; ?></td>
                                <td><?php echo $entry['td']; ?></td>
                                <td><?php echo $entry['ki']; ?></td>
                                <td><?php echo $m->team1_score .' - '. $m->team2_score; ?></td>
                                <td><?php echo matchresult_icon((($m->is_draw) ? 'D' : (($m->winner == $p->owned_by_team_id) ? 'W' : 'L'))); ?></td>
                                <td><a href='javascript:void(0)' onClick="window.open('index.php?section=matches&amp;type=report&amp;mid=<?php echo $m->match_id;?>');"><?php echo $lng->getTrn('common/view');?></a></td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>
    <?php
}

private function _recentGames()
{
    global $lng;
    $p = $this; // Copy. Used instead of $this for readability.

    ?>
    <div class="row">
        <div class="boxWide">
            <div class="boxTitle<?php echo T_HTMLBOX_MATCH;?>"><a href='javascript:void(0);' onClick="slideToggleFast('played');"><strong>[+/-]</strong></a> &nbsp;<?php echo $lng->getTrn('common/recentmatches');?></div>
            <div class="boxBody" id="played">
                <?php
                HTMLOUT::recentGames(STATS_PLAYER, $p->player_id, false, false, false, false, array('n' => MAX_RECENT_GAMES, 'url' => urlcompile(T_URL_PROFILE,T_OBJ_PLAYER,$p->player_id,false,false)));
                ?>
            </div>
        </div>
    </div>
    <?php
}

private function _injuryHistory()
{
    global $lng, $T_INJS;
    $p = $this; // Copy. Used instead of $this for readability.
    list($injhist, $stats, $match_objs) = $p->getInjHistory();

    ?>
    <div class="row">
        <div class="boxWide">
            <div class="boxTitle<?php echo T_HTMLBOX_STATS;?>"><a href='javascript:void(0);' onClick="slideToggleFast('injhist');"><strong>[+/-]</strong></a> &nbsp;<?php echo $lng->getTrn('profile/player/injhist');?></div>
            <div class="boxBody" id="injhist">
                <table class="common">
                    <tr>
                        <td><strong><?php echo $lng->getTrn('common/injs');?></strong></td>
                        <td><strong><?php echo $lng->getTrn('common/tournament');?></strong></td>
                        <td><strong><?php echo $lng->getTrn('common/opponent');?></strong></td>
                        <td><strong>MVP</strong></td>
                        <td><strong>Cp</strong></td>
                        <td><strong>Td</strong></td>
                        <td><strong>Int</strong></td>
                        <td><strong>Cas</strong></td>
                        <td><strong><?php echo $lng->getTrn('common/score');?></strong></td>
                        <td><strong><?php echo $lng->getTrn('common/result');?></strong></td>
                        <td><strong><?php echo $lng->getTrn('common/dateplayed');?></strong></td>
                        <td><strong><?php echo $lng->getTrn('common/match');?></strong></td>
                    </tr>
                    <?php
                    foreach (array_keys($injhist) as $mid) {
                        $m = $match_objs[$mid];
                        foreach ($injhist[$mid] as $k => $v) {
                            $injhist[$mid][$k] = ucfirst(strtolower($T_INJS[$v]));
                        }
                        ?>
                        <tr>
                        <td><?php echo implode(', ', $injhist[$mid]); ?></td>
                        <td><?php echo get_parent_name(T_NODE_MATCH, $m->match_id, T_NODE_TOURNAMENT);?></td>
                        <td><?php echo ($p->owned_by_team_id == $m->team1_id) ? $m->team2_name : $m->team1_name; ?></td>
                        <td><?php echo $stats[$mid]['mvp']; ?></td>
                        <td><?php echo $stats[$mid]['cp']; ?></td>
                        <td><?php echo $stats[$mid]['td']; ?></td>
                        <td><?php echo $stats[$mid]['intcpt']; ?></td>
                        <td><?php echo $stats[$mid]['bh']+$stats[$mid]['si']+$stats[$mid]['ki']; ?></td>
                        <td><?php echo $m->team1_score .' - '. $m->team2_score; ?></td>
                        <td><?php echo matchresult_icon((($m->is_draw) ? 'D' : (($m->winner == $p->owned_by_team_id) ? 'W' : 'L'))); ?></td>
                        <td><?php echo textdate($m->date_played, false, false);?></td>
                        <td><a href='javascript:void(0)' onClick="window.open('index.php?section=matches&amp;type=report&amp;mid=<?php echo $m->match_id;?>');"><?php echo $lng->getTrn('common/view');?></a></td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>
    <?php
}

private function _ES()
{
    global $lng;
    ?>
    <div class="row">
        <div class="boxWide">
            <div class="boxTitle<?php echo T_HTMLBOX_STATS;?>"><a href='javascript:void(0);' onClick="slideToggleFast('ES');"><strong>[+/-]</strong></a> &nbsp;<?php echo $lng->getTrn('common/extrastats');?></div>
            <div class="boxBody" id="ES" style='display:none;'>
                <?php
                HTMLOUT::generateEStable($this);
                ?>
            </div>
        </div>
    </div>
    <?php
}

}
