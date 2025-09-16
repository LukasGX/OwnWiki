<input type="text" name="target" placeholder="Ziel" value="<?php echo isset($_GET['t']) ? htmlspecialchars(strip_tags($_GET['t'])) : ''; ?>" />
<select name="scope">
    <option value="sitewide">Gesamte Seite</option>
    <?php
    foreach (glob('pages/*/__namespace__.json') as $nsFile) {
        $json = file_get_contents($nsFile);
        $content = json_decode($json, true);

        $id = htmlspecialchars($content["id"]);
        $name = htmlspecialchars($content["name"]);
        echo "<option value='ns-$id'>Namensraum $name</option>";
    }
    ?>
</select>
<input type="checkbox" name="optCreateAccounts"> Account-Erstellung erlauben<br />
<input type="checkbox" name="optSendEmails"> Senden von E-Mails erlauben<br />
<input type="checkbox" name="optOwnDiscussion"> Bearbeitunger der eigenen Diskussionsseite erlauben<br /><br />

<input id="datetimePicker" type="text" name="durationUntil" placeholder="tt.mm.jjjj --:--" />
Sperrzeit: <span data-display="distance"></span>

<div></div>