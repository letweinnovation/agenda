<?php

class TrelloHelper
{
    private $apiKey;
    private $token;
    private $boardId;
    private $baseUrl = 'https://api.trello.com/1';

    public function __construct($config)
    {
        $this->apiKey = $config['trello_api_key'];
        $this->token = $config['trello_token'];
        $this->boardId = $config['trello_board_id'];
    }

    private function makeRequest($endpoint, $params = [])
    {
        $params['key'] = $this->apiKey;
        $params['token'] = $this->token;
        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            // Silently fail or log in a real app, for now return empty
            return null;
        }
        curl_close($ch);

        return json_decode($response, true);
    }

    public function searchCardByCompanyName($companyName)
    {
        // Trello search API is powerful. We can search for the card directly.
        // However, user asked to check if it has compatible title (min 2 words).

        if (str_word_count($companyName) < 2) {
            throw new Exception("Por favor, digite pelo menos duas palavras para buscar a empresa.");
        }

        // Search for open cards on the specific board
        $query = "board:\"{$this->boardId}\" is:open name:\"{$companyName}\"";

        $results = $this->makeRequest('/search', [
            'query' => $query,
            'modelTypes' => 'cards',
            'cards_limit' => 10,
            'card_fields' => 'name,idMembers'
        ]);

        if (empty($results['cards'])) {
            // Try a looser search if exact match fails, or rely on Trello's fuzzy search
            return [];
        }

        return $results['cards'];
    }

    public function getMemberDetails($memberId)
    {
        return $this->makeRequest("/members/{$memberId}", ['fields' => 'username,fullName']);
    }
}
