<?php
	# Подключаем библиотеку
	require_once('library/SimpleAmoApi.php');
	
	# Инициализируем объект для работы с API
	$amoAPI = new SimpleAmoApi();
	
	/*
	 * Получаем информацию об аккаунте
	 * Тут можно подсмотреть список воронок с статусами (их ID)
	 * Тут можно узнать ID кастомных полей и ID пользователей (когда нужно добавить в лид ответственного)
	*/
	$account = $amoAPI->getAccount();
	
	echo '<pre>';
	print_r($account);
	echo '</pre>';
	die('Тест для первого запуска скрипта');


	# Добавляем лид с пользователем и примечанием к нему
	
	# Тестовые контакты
	$email = 'mail@mail.com';
	$name = 'Павел Мазур';
	$phone = '+380503332211';
	$comments = "Любая тестовая информация с переносами через \n для примечания";
	
	
	# Заголовок лида в АМО
	$leadTitle = $email.' (Тестовая)';

	# Создаем массив для новой сделки
	$lead['add'] = [
		[
			'name' => $leadTitle
		]
	];
	
	# Задаем ID воронки и статуса
	$lead['add'][0]['pipeline_id'] = 0000000;
	$lead['add'][0]['status_id'] = 0000000;
	
	# Добавляем лид
	$addLead = $amoAPI->postLeads($lead);
	
	# Получаем ID нового лида
	$leadId = $addLead[0]->id;
	
	# Проверяем, есть ли контакт с таким Email в системе
	# Вместо Email можно указывать любую информацию для поиска, например телефон
	$contacts = $amoAPI->getContacts( 'query='.$email );
	
	# Если контакт есть, то добавляем ему сделку
	/*
		Примечание:
		Иногда, в зависимости от специфики, контактов может быть несколько при поиске.
		Функция возвращает нам массив с найденными контактами в $contacts.
		Если вам нужно добавить сделку всем найденным контактам, то нужно дописать для этого цикл.
		Я в своем примере добавляю только для первого найденного и все.
	*/
	if ( !empty($contacts) ) :
		# ID - контакта
		$contactId = $contacts[0]->id;
		
		# Если у контакта уже есть лиды, дополняем список новым
		if ( !empty($contacts[0]->leads->id) ) {
			$leadsList = $contacts[0]->leads->id;
			$leadsList[] = $leadId;
		# Или добавляем с чистого листа (первы лид контакта)
		} else {
			$leadsList = [
				$leadId
			];
		}
		
		# Создаем массив для обновления контакта
		$upContacts['update'] = [
			[
				'id' => $contactId,
				'updated_at' => time(),
				'leads_id' => $leadsList
			]
		];
		
		# Обновляем контакт
		$updateContact = $amoAPI->postContacts( $upContacts );
		
		# Получаем ID обновленного контакта
		$contactId = $updateContact[0]->id;
	
	# Если контакта нет (не найден), то создаем новый
	else :
		# Имя обязательно
		$name = ( $name ) ? $name : 'Без имени';
		
		# Создаем массив для добавления контакта
		$contacts['add'] = [
			[
				'name' => $name,
				'leads_id' => [
					$leadId
				],
			]
		];
		
		# Добавляем кастомные поля контакту (Email и телефон, часто еще город)
		/*
			Примечание:
			ID кастомных полей всегда разные для каждой админки, их можно подсмотреть
			в $account выше. Аналогично кастомные поля есть и у лида, их ID можно
			подсмотреть там же
		*/
		if ( $email ) :
			$contacts['add'][0]['custom_fields'][] = [
				'id' => 195505,
				'values' => [
					[
						'value' => $email,
						'enum' => 'WORK'
					]
				]
			];
		endif;
		
		if ( $phone ) :
			$contacts['add'][0]['custom_fields'][] = [
				'id' => 195503,
				'values' => [
					[
						'value' => $phone,
						'enum' => 'MOB'
					]
				]
			];
		endif;
		
		# Добавляем контакт
		$addContact = $amoAPI->postContacts( $contacts );
		
		# Получаем ID нового контакта
		$contactId = $addContact[0]->id;
	endif;
	
	# Добавляем примечание лиду
	if ( $leadId && $comments ) :
		
		# Создаем массив для добавления примечания
		$notes['add'] = [
			[
				'element_id' => $leadId,
				'element_type' => 2,
				'note_type' => 4,
				'text' => $comments
			]
		];
		
		# Добавляем примечание
		$addNote = $amoAPI->postNotes($notes);
		
		# Получаем ID нового примечания
		$noteId = $addNote[0]->id;
	endif;
