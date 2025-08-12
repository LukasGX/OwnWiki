[[NOTOC]]

<form action="backend/protect.php" method="post">
    <input type="text" name="page" placeholder="Seitenname" value="[[ASKEDSITE]]">
    <select name="protection">
        <option value="none">Kein Schutz</option>
        <option value="semiprotected">Halbschutz (autoconfirmed)</option>
        <option value="protected">Schutz (admin)</option>
    </select>
    <input type="submit" value="Senden">
</form>
