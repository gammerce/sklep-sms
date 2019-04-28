#if defined _client_print_color_included
    #endinput
#endif
#define _client_print_color_included

enum {
    print_team_default = 0,
    print_team_grey = -1,
    print_team_red = -2,
    print_team_blue = -3,
};

new teamNames[][] = {
    "UNASSIGNED",
    "TERRORIST",
    "CT",
    "SPECTATOR"
}

stock client_print_color(id, color = print_team_default, const msg[], {Float,Sql,Result,_}:...)
{
    new message[192], team, change, index, msgType;

    if (color > print_team_default) {
        color = print_team_default;
    }

    if (color == print_team_default) {
        message[0] = 0x04;
    } else {
        message[0] = 0x03;
    }

    vformat(message[1], charsmax(message) - 1, msg, 4);

    if (id) {
        msgType = MSG_ONE;
        index = id;
    } else {
        index = find_first_player();
        msgType = MSG_ALL;
    }

    team = get_user_team(index);
    change = color_selection(index, msgType, color);

    show_color_message(index, msgType, message);

    if (change) {
        team_info(index, msgType, teamNames[team]);
    }
}

stock show_color_message(id, type, message[])
{
    static msgSayText;

    if (!msgSayText) {
        msgSayText = get_user_msgid("SayText");
    }

    message_begin(type, msgSayText, _, id);
    write_byte(id)
    write_string(message);
    message_end();
}

stock team_info(id, type, team[])
{
    static bool:teamInfoUsed, msgTeamInfo;

    if (!teamInfoUsed) {
        msgTeamInfo = get_user_msgid("TeamInfo");
        teamInfoUsed = true;
    }

    message_begin(type, msgTeamInfo, _, id);
    write_byte(id);
    write_string(team);
    message_end();

    return 1;
}

stock color_selection(index, type, color)
{
    switch (color) {
        case print_team_red: {
            return team_info(index, type, teamNames[1]);
        } case print_team_blue: {
            return team_info(index, type, teamNames[2]);
        } case print_team_grey: {
            return team_info(index, type, teamNames[0]);
        }
    }

    return 0;
}

stock find_first_player()
{
    static maxPlayers;

    if (!maxPlayers) {
        maxPlayers = get_maxplayers();
    }

    new i = -1;

    while (i <= maxPlayers) {
        if (is_user_connected(++i)) return i;
    }

    return -1;
}