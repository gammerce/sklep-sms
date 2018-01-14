<?php

$heart->register_block("services_buttons", "BlockServicesButtons");

class BlockServicesButtons extends Block
{
	public function get_content_class()
	{
		return "services_buttons";
	}

	public function get_content_id()
	{
		return "services_buttons";
	}

	protected function content($get, $post)
	{
		global $heart, $user, $lang, $templates;

		$services = "";
		foreach ($heart->get_services() as $service) {
			if (($service_module = $heart->get_service_module($service['id'])) === null || !$service_module->show_on_web()) {
				continue;
			}

			if (!$heart->user_can_use_service($user->getUid(), $service)) {
				continue;
			}

			$services .= create_dom_element("li", create_dom_element("a", $service['name'], array(
				'href' => "index.php?pid=purchase&service=" . urlencode($service['id'])
			)));
		}

		$output = eval($templates->render("services_buttons"));

		return $output;
	}
}