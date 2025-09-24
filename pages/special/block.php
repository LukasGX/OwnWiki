<input type="text" id="target" placeholder="Ziel" value="<?php echo isset($_GET['t']) ? htmlspecialchars(strip_tags($_GET['t'])) : ''; ?>" />
<select id="scope">
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
<input type="checkbox" id="optCreateAccounts"> <label for="optCreateAccounts">Account-Erstellung erlauben</label><br />
<input type="checkbox" id="optSendEmails"> <label for="optSendEmails">Senden von E-Mails erlauben</label><br />
<input type="checkbox" id="optOwnDiscussion"> <label for="optOwnDiscussion">Bearbeitungen der eigenen Diskussionsseite erlauben</label><br /><br />

<input id="datetimePicker" type="text" placeholder="tt.mm.jjjj --:--" />
Sperrzeit: <span data-display="distance"></span><br /><br />
<input type="text" id="reason" placeholder="Grund" />
<button class="extDeleteButton" id="block-btn">Sperren</button>

<div></div>