<?php

namespace MS\Concerns;

use MS\Services\MessageParser;

trait SerializesMailMessage {
	public function serializeMailMessage( $messages, $replacements = [] ) {
		$main      = [];
		$followups = [];
		$drips     = [];
		$onclick   = [];

		$delay = 0;

		foreach ( $messages as $message ) {
			$fields                 = [];
			$fields['id']           = $message->id;
			$fields['campaign_id']  = $message->campaign_id;
			$fields['subject']      = MessageParser::parse( $message->subject, $replacements );
			$fields['message']      = MessageParser::parse( $message->message, $replacements );
			$fields['message_type'] = $message->message_type;

			switch ( $message->message_type ) {
				case 'main':
					$main = $fields;
					break;
				case 'followup':
					$fields['reason'] = $message->reason;
					$fields['delay']  = $message->delay - $delay;
					$delay            += $message->delay;
					$followups[]      = $fields;
					break;
				case 'onclick':
					$fields['link']  = $message->link;
					$fields['delay'] = $message->delay;
					$onclick[]       = $fields;
					break;
				case 'drips':
					$fields['delay'] = $message->delay;
					$drips[]         = $fields;
					break;
				default:
					// something unexpected
			}
		}

		return array_merge(
			$main,
			array(
				'drips'     => $drips,
				'onclick'   => $onclick,
				'followups' => $followups,
			)
		);
	}

	/**
	 * Split a name into first name and last name
	 *
	 * @param $name
	 *
	 * @return array
	 */
	public function split_name( $name ) {
		$parts = explode( ' ', trim( $name ) );

		// name has only 1 part
		if ( count( $parts ) === 1 ) {
			return array( $name, '' );
		}

		// name has more than one part
		$last_name  = array_pop( $parts );
		$first_name = implode( ' ', $parts );

		return array( $first_name, $last_name );
	}

	/**
	 * @param null|object $subscriber
	 * @param null|object $sender
	 *
	 * @return array
	 */
	public function getReplacements( $subscriber = null, $sender = null ) {
		$replacements = [];

		if ( $subscriber ) {
			$replacements['name'] = $subscriber->name;

			$name_parts                 = $this->split_name( $subscriber->name );
			$replacements['first_name'] = $name_parts[0];
			$replacements['last_name']  = $name_parts[1];

			$replacements['email'] = $subscriber->email;
		}

		if ( $sender ) {
			$replacements['your_name']  = $sender->name;
			$replacements['your_email'] = $sender->email;
			$replacements['signature']  = $sender->signature;
		}

		return $replacements;
	}
}