<?php

$heart->register_block("user_buttons", "BlockUserButtons");

class BlockUserButtons extends Block {

	public function get_content_class() {
		return is_logged() ? "user_buttons" : "loginarea";
	}

	public function get_content_id() {
		return "user_buttons";
	}

	protected function content($get, $post) {
		global $lang;

		if (is_logged()) {
			global $heart, $user;

			// Panel Admina
			if (get_privilages("acp", $user))
				$acp_button = create_dom_element("li", create_dom_element("a", $lang['acp'], array(
					'href' => "admin.php"
				)));

			// Doładowanie portfela
			if ($heart->user_can_use_service($user['uid'], $heart->get_service("charge_wallet")))
				$charge_wallet_button = create_dom_element("li", create_dom_element("a", $lang['charge_wallet'], array(
					'href' => "index.php?pid=purchase&service=charge_wallet"
				)));

			eval("\$output = \"" . get_template("user_buttons") . "\";");
		} else
			eval("\$output = \"" . get_template("loginarea") . "\";");

		return $output;
	}

}