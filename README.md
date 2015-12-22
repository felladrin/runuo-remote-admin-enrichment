# RunUO Remote Admin Enrichment

This project contains PHP scripts and web page control panel to control RunUO shard. A feature enrichment script also included in this project to allow you to broadcast, save, restart, shutdown your RunUO shard using web interface. The web interface does not have to be on the same server with your RunUO. It can connect to any running RunUO server which you have staff account on it.

**Basic remote admin features provided in RunUO official code:**

- Search RunUO game account implemtation incomplete in current version of web control panel
- Add RunUO game account
- Modify RunUO game account
    - Change password
    - Change access level
    - Ban account

**Enriched remote admin features in this project:**

- Broadcast on RunUO shard same as you broadcast in game using staff account
- Save RunUO shard instantly
- Restart RunUO shard with or without saving
- Shutdown RunUO shard with or without saving

You may also add functionality of creating new RunUO account instantly to your shard website, using the PHP function library in this project.

**Original author: Antony Ho** (<https://code.google.com/p/runuo-remote-admin-enrichment/>)

## Install: Web Control Panel

Upload all files under `web` directory onto your web server directory. Browse the `runuo_remote_admin.php`, you will see a logon page. Input the RunUO server IP/domain, port, then your RunUO admin account to login. The RunUO account can be administrator, owner, developer or game master access level in RunUO server.

By default, `USING_REMOTE_ADMIN_ENRICHMENT` is set to _TRUE_, so it expects you'll install RunUO script. Set it to false, on top of the `runuo_remote_admin.php` file, if you don't intend to use the enriched remote admin features.

## Install: Optional RunUO Script

To enable the enriched remote admin features, all you need to do is drop `CustomRemoteAdminPacketHandlers.cs` anywhere in your 'Scripts' folder.

## Screenshot

![Screenshot](http://i.imgur.com/ODWRDTA.png)
