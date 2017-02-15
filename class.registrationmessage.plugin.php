<?php

$PluginInfo['registrationmessage'] = [
    'Name' => 'Registration Message',
    'Description' => 'Sends a configurable message to users immediately after registration.',
    'Version' => '0.3',
    'RequiredApplications' => ['Vanilla' => '2.2'],
    'MobileFriendly' => true,
    'SettingsUrl' => 'settings/registrationmessage',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'Bleistivt',
    'AuthorUrl' => 'http://bleistivt.net',
    'License' => 'GNU GPL2'
];

class RegistrationMessagePlugin extends Gdn_Plugin {
    public function userModel_afterRegister_handler($sender, $args) {
        $user = Gdn::userModel()->getID($args['UserID']);

        // Send welcome mail.
        $email = new Gdn_Email();
        $email
            ->setFormat('html')
            ->subject(str_replace('%%NAME%%', $user->Name, c('RegistrationMessage.MailSubject')))
            ->message(str_replace('%%NAME%%', $user->Name, c('RegistrationMessage.MailBody')))
            ->to($user->Email);
        try {
            $email->send();
        } catch (Exception $e) {
            throw $e;
        }

        // Send welcome PM.
        if (!c('EnabledApplications.Conversations')) {
            return;
        }

        $conversationMessage = [
            'Body' => str_replace('%%NAME%%', $user->Name, c('RegistrationMessage.MessageBody')),
            'Format' => 'Html',
            'InsertUserID' => c('RegistrationMessage.User', Gdn::userModel()->getSystemUserID()),
            'RecipientUserID' => [$args['UserID']]
        ];
        if (c('Conversations.Subjects.Visible')) {
            $conversationMessage['Subject'] = str_replace('%%NAME%%', $user->Name, c('RegistrationMessage.MessageSubject'));
        }

        (new ConversationModel())->save($conversationMessage, new ConversationMessageModel());
    }


    public function settingsController_registrationMessage_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->addSideMenu('settings/registrationmessage');

        $conf = new ConfigurationModule($sender);
        $conf->initialize([
            'RegistrationMessage.MailSubject' => [
                'Control' => 'textbox',
                'LabelCode' => 'Subject of Welcome Mails.',
                'Description' => 'HTML is allowed. You can use <code>%%NAME%%</code> as a placeholder for the user\'s name.'
            ],
            'RegistrationMessage.MailBody' => [
                'Control' => 'textbox',
                'LabelCode' => 'Write a message which will be mailed to newly registered users.',
                'Description' => 'HTML is allowed. You can use <code>%%NAME%%</code> as a placeholder for the user\'s name.',
                'Options' => ['MultiLine' => true]
            ],
            'RegistrationMessage.MessageSubject' => [
                'Control' => 'textbox',
                'LabelCode' => 'Subject of Welcome Messages.',
                'Description' => 'This will only be used if you use subjects in conversation messages.'
            ],
            'RegistrationMessage.MessageBody' => [
                'Control' => 'textbox',
                'LabelCode' => 'Write a message which will be send as a PM to newly registered users.',
                'Description' => 'HTML is allowed. You can use <code>%%NAME%%</code> as a placeholder for the user\'s name.',
                'Options' => ['MultiLine' => true]
            ]
        ]);

        $sender->title('Registration Message');
        $conf->renderAll();
    }


    public function base_getAppSettingsMenuItems_handler($sender, &$args) {
        $args['SideMenu']->addLink(
            'Users',
            'Registration Message',
            'settings/registrationmessage',
            'Garden.Settings.Manage'
        );
    }


    public function setup() {
        touchConfig('RegistrationMessage.MailSubject', 'Welcome to '.c('Garden.Title'));
        touchConfig('RegistrationMessage.MailBody', 'Hi %%NAME%%, welcome to the community!');
        touchConfig('RegistrationMessage.MessageSubject', 'Welcome to '.c('Garden.Title'));
        touchConfig('RegistrationMessage.MessageBody', 'Hi %%NAME%%, welcome to the community!');
    }
}
