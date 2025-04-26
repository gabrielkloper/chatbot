<?php


require __DIR__ . '/vendor/autoload.php';

use core\Megaapi;
use core\Model;

// Megaapi::configWebhook("https://a086-2804-3d28-43-e0e7-b018-c156-364e-8370.ngrok-free.app/chatbotMegaapi/index.php", true);

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);


file_put_contents('webhook.log', print_r(json_decode(file_get_contents('php://input'), true), true) . "\n", FILE_APPEND);

// Função para fazer log de erros com mais detalhes
function logError($message, $data = null) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message . "\n";
    
    if ($data !== null) {
        $logMessage .= "Raw Data: " . $data . "\n";
        
        // Adiciona informações sobre o tipo de dados
        $logMessage .= "Data Type: " . gettype($data) . "\n";
        
        // Se for uma string, tenta decodificar como JSON
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $logMessage .= "JSON Decoded Data:\n" . print_r($decoded, true) . "\n";
            } else {
                $logMessage .= "JSON Decode Error: " . json_last_error_msg() . "\n";
            }
        }
    }
    
    $logMessage .= "------------------------\n";
    file_put_contents(__DIR__ . '/error.log', $logMessage, FILE_APPEND);
}

// Função para substituir status do ack
function replaceAck($status) {
    return $status;
}

// Função para salvar os dados no banco
function salvarAgendamento($dados) {
    $tipoAtendimento = $dados['tipo_atendimento'];
    $dadosParaSalvar = [
        'nome_completo' => $dados['nome_completo'] ?? null,
        'identidade' => $dados['identidade'] ?? null,
        'cpf' => $dados['cpf'] ?? null,
        'endereco' => $dados['endereco'] ?? null,
        'convenio' => $dados['convenio'] ?? null,
        'numero_carteira' => $dados['numero_carteira'] ?? null,
        'medico_preferencia' => $dados['medico_preferencia'] ?? null,
        'dia_hora_interesse' => $dados['dia_hora_interesse'] ?? null,
        'motivo_ausencia' => $dados['motivo_ausencia'] ?? null,
        'hospital_internacao' => $dados['hospital_internacao'] ?? null,
        'tipo_atendimento' => $tipoAtendimento,
        'numero_contato' => $dados['numero_contato'] ?? null,
        'descricao_feedback' => $dados['descricao_feedback'] ?? null,
        // 'nome_contato' => $dados['nome_contato'] ?? null,
    ];

    // Salva no banco usando o Model
    Model::insert($dadosParaSalvar, 'agendamentos');
}

// Função para buscar agendamentos
function buscarAgendamentos() {
    $agendamentos = Model::select('*', '', 'agendamentos');
    return $agendamentos;
}

// Função para enviar informações para outro número
function enviarParaOutroNumero($numero, $mensagem) {
    Megaapi::text($numero, $mensagem);
}

// Função para processar e salvar dados do usuário
function processarDados($chatId, $message, $tipo) {
    static $dadosUsuario = [];
    static $camposEsperados = [];
    
    // Inicializa campos esperados se não existirem
    if (empty($camposEsperados)) {
        if ($tipo === 'primeira_consulta') {
            $camposEsperados = [
                'nome_completo',
                'identidade',
                'cpf',
                'endereco',
                'convenio',
                'numero_carteira',
                'medico_preferencia',
                'dia_hora_interesse'
            ];
        } elseif ($tipo === 'consulta_retorno') {
            $camposEsperados = [
                'nome_completo',
                'convenio',
                'numero_carteira',
                'medico_preferencia',
                'dia_hora_interesse'
            ];
        } elseif ($tipo === 'falta') {
            $camposEsperados = [
                'nome_completo',
                'motivo_ausencia',
                'hospital_internacao'
            ];
        }
    }

    // Salva o dado recebido
    if (!empty($camposEsperados)) {
        $campo = array_shift($camposEsperados);
        $dadosUsuario[$campo] = $message;
        
        // Se ainda há campos para preencher, solicita o próximo
        if (!empty($camposEsperados)) {
            $proximoCampo = str_replace('_', ' ', ucfirst($camposEsperados[0]));
            Megaapi::text($chatId, "Por favor, informe {$proximoCampo}:");
        }
    }

    // Se todos os campos foram preenchidos, salva no banco
    if (empty($camposEsperados)) {
        $dadosUsuario['tipo_atendimento'] = $tipo;
        salvarAgendamento($dadosUsuario);
        
        // Envia para número específico baseado no tipo
        $numeroDestino = '';
        switch ($tipo) {
            case 'primeira_consulta':
            case 'consulta_retorno':
                $numeroDestino = '5521998362972'; // Número da recepção
                break;
            case 'falta':
                $numeroDestino = '5521998362972'; // Número da enfermagem
                break;
        }
        
        if ($numeroDestino) {
            $mensagem = "Novo atendimento - {$tipo}:\n\n";
            foreach ($dadosUsuario as $campo => $valor) {
                $mensagem .= ucfirst(str_replace('_', ' ', $campo)) . ": $valor\n";
            }
            enviarParaOutroNumero($numeroDestino, $mensagem);
        }
        
        // Limpa os dados após salvar
        $dadosUsuario = [];
        return true;
    }
    
    return false;
}

// Função para extrair a mensagem do payload
function extractMessage($array) {
    if (isset($array["message"]["conversation"])) {
        return $array["message"]["conversation"];
    }
    if (isset($array["message"]["extendedTextMessage"]["text"])) {
        return $array["message"]["extendedTextMessage"]["text"];
    }
    return null;
}

// Função para extrair o chat ID do payload
function extractChatId($array) {
    if (isset($array["key"]["remoteJid"])) {
        return $array["key"]["remoteJid"];
    }
    return null;
}

// Função para gerenciar o estado da conversa
function getConversationState($chatId) {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (!isset($_SESSION['conversations'])) {
        $_SESSION['conversations'] = [];
    }
    
    if (!isset($_SESSION['conversations'][$chatId])) {
        $_SESSION['conversations'][$chatId] = [
            'menu' => 'main',
            'fluxo' => null,
            'subfluxo' => null,
            'dados' => [],
            'campo' => 0,
            'perguntas' => []
        ];
    }
    
    return $_SESSION['conversations'][$chatId];
}

// Função para atualizar o estado da conversa
function updateConversationState($chatId, $state) {
    $_SESSION['conversations'][$chatId] = $state;
}

// Iniciando a sessão para controle de estado
session_start();

// Função para normalizar o formato da mensagem
function normalizeMessageFormat($array) {
    // If message type is not set, return null
    if (!isset($array['messageType'])) {
        return null;
    }

    // For message.ack types, return a specific format
    if ($array['messageType'] === 'message.ack') {
        return [
            'messageType' => 'message.ack',
            'instance_key' => $array['instance_key'] ?? null,
            'key' => [
                'fromMe' => $array['key']['fromMe'] ?? false,
                'remoteJid' => $array['key']['remoteJid'] ?? null,
                'id' => $array['key']['id'] ?? null
            ],
            'update' => [
                'status' => $array['update']['status'] ?? null
            ]
        ];
    }

    $normalized = [
        'key' => [
            'fromMe' => false,
            'remoteJid' => null,
            'id' => null
        ],
        'messageType' => null,
        'message' => [
            'conversation' => null
        ]
    ];

    // Normalize the key fields
    if (isset($array['key'])) {
        $normalized['key']['fromMe'] = !empty($array['key']['fromMe']);
        $normalized['key']['remoteJid'] = $array['key']['remoteJid'] ?? null;
        $normalized['key']['id'] = $array['key']['id'] ?? null;
    }

    // Normalize message type
    $normalized['messageType'] = $array['messageType'] ?? null;

    // Normalize the message content
    if (isset($array['message'])) {
        if (isset($array['message']['conversation'])) {
            $normalized['message']['conversation'] = $array['message']['conversation'];
        } elseif (isset($array['message']['extendedTextMessage']['text'])) {
            $normalized['message']['conversation'] = $array['message']['extendedTextMessage']['text'];
        }
    } else if (isset($array['conversation'])) {
        $normalized['message']['conversation'] = $array['conversation'];
    }

    return $normalized;
}

// Função para processar cada etapa do fluxo
function processStep($chatId, $message, $currentState) {
    $step = $currentState ? $currentState['current_step'] : 'initial';
    $dados = $currentState ? $currentState['dados'] : [];
    $response = '';
    
    // Verificar se é timeout
    if ($step === 'timeout') {
        Model::resetChatState($chatId);
        return "Devido à inatividade, seu atendimento foi encerrado. Por favor, digite 'oi' para iniciar um novo atendimento.";
    }
    
    switch ($step) {
        case 'initial':
            if (isGreeting($message)) {
                Model::updateChatState($chatId, 'menu');
                return "Seja bem-vindo à Nefroclinicas unidade xxx. Digite a opção desejada abaixo:\n\n" .
                       "1 - Marcação de Consultas\n" .
                       "2 - Solicitação de Vaga para Diálise\n" .
                       "3 - Notificar uma Falta ou Ausência de Sessão de Diálise\n" .
                       "4 - Caso queira deixar um elogio, sugestão ou reclamação";
            }
            return "Olá! Digite 'oi' para começar.";

        case 'menu':
            switch($message) {
                case "1":
                    Model::updateChatState($chatId, 'consulta_tipo');
                    return "Digite:\n1 - Para primeira consulta\n2 - Caso não seja sua primeira consulta conosco";
                case "2":
                    Model::resetChatState($chatId);
                    return "Para mais informações de vagas em nossa unidade, segue o contato da nossa chefe de Captação: Cynthia - 21 98987-6009\n\nA Nefroclinicas xxx agradece o contato.";
                case "3":
                    Model::updateChatState($chatId, 'falta_nome');
                    return "Por favor, informe o nome completo do paciente:";
                case "4":
                    Model::updateChatState($chatId, 'feedback');
                    return "Por favor, descreva para nós o seu elogio, sugestão ou reclamação:";
                default:
                    return "Opção inválida. Digite um número de 1 a 4.";
            }

        case 'consulta_tipo':
            switch($message) {
                case "1":
                    Model::updateChatState($chatId, 'primeira_consulta_nome', ['tipo' => 'primeira_consulta']);
                    return "Por favor, informe seu nome completo:";
                case "2":
                    Model::updateChatState($chatId, 'retorno_nome', ['tipo' => 'consulta_retorno']);
                    return "Por favor, informe seu nome completo:";
                default:
                    return "Opção inválida. Digite 1 para primeira consulta ou 2 para retorno.";
            }

        // Fluxo primeira consulta
        case 'primeira_consulta_nome':
            $dados['nome_completo'] = $message;
            Model::updateChatState($chatId, 'primeira_consulta_identidade', $dados);
            return "Por favor, informe seu número de identidade:";

        case 'primeira_consulta_identidade':
            $dados['identidade'] = $message;
            Model::updateChatState($chatId, 'primeira_consulta_cpf', $dados);
            return "Por favor, informe seu CPF:";

        case 'primeira_consulta_cpf':
            $dados['cpf'] = $message;
            Model::updateChatState($chatId, 'primeira_consulta_endereco', $dados);
            return "Por favor, informe seu endereço completo:";

        case 'primeira_consulta_endereco':
            $dados['endereco'] = $message;
            Model::updateChatState($chatId, 'primeira_consulta_convenio', $dados);
            return "Por favor, informe seu convênio:";

        case 'primeira_consulta_convenio':
            $dados['convenio'] = $message;
            Model::updateChatState($chatId, 'primeira_consulta_carteira', $dados);
            return "Por favor, informe o número da sua carteira do convênio:";

        case 'primeira_consulta_carteira':
            $dados['numero_carteira'] = $message;
            Model::updateChatState($chatId, 'primeira_consulta_medico', $dados);
            return "Por favor, informe o médico de sua preferência (se houver):";

        case 'primeira_consulta_medico':
            $dados['medico_preferencia'] = $message;
            Model::updateChatState($chatId, 'primeira_consulta_data', $dados);
            return "Por favor, informe o dia e hora de sua preferência:";

        case 'primeira_consulta_data':
            $dados['dia_hora_interesse'] = $message;
            $dados['tipo_atendimento'] = 'primeira_consulta';
            $dados['numero_contato'] = extrairNumeroCelular($chatId);
            
            salvarAgendamento($dados);

            // Enviar mensagem para outro número
            $mensagemRedirecionada = "Primeiro Agendamento:\n";
            $mensagemRedirecionada .= "Nome: " . $dados['nome_completo'] . "\n";
            $mensagemRedirecionada .= "Identidade: " . $dados['identidade'] . "\n";
            $mensagemRedirecionada .= "CPF: " . $dados['cpf'] . "\n";
            $mensagemRedirecionada .= "Endereço: " . $dados['endereco'] . "\n";
            $mensagemRedirecionada .= "Convênio: " . $dados['convenio'] . "\n";
            $mensagemRedirecionada .= "Nº Carteira: " . $dados['numero_carteira'] . "\n";
            $mensagemRedirecionada .= "Médico Preferência: " . $dados['medico_preferencia'] . "\n";
            $mensagemRedirecionada .= "Data/Hora Interesse: " . $dados['dia_hora_interesse'] . "\n";
            $mensagemRedirecionada .= "Contato: +" . $dados['numero_contato'];
            
            enviarParaOutroNumero('5521998362972', $mensagemRedirecionada);
            
            Model::resetChatState($chatId);
            return "Obrigado! Suas informações foram registradas com sucesso. Em breve entraremos em contato.";

        // Fluxo consulta retorno
        case 'retorno_nome':
            $dados['nome_completo'] = $message;
            Model::updateChatState($chatId, 'retorno_convenio', $dados);
            return "Por favor, informe seu convênio:";

        case 'retorno_convenio':
            $dados['convenio'] = $message;
            Model::updateChatState($chatId, 'retorno_carteira', $dados);
            return "Por favor, informe o número da sua carteira do convênio:";

        case 'retorno_carteira':
            $dados['numero_carteira'] = $message;
            Model::updateChatState($chatId, 'retorno_medico', $dados);
            return "Por favor, informe o médico de sua preferência (se houver):";

        case 'retorno_medico':
            $dados['medico_preferencia'] = $message;
            Model::updateChatState($chatId, 'retorno_data', $dados);
            return "Por favor, informe o dia e hora de sua preferência:";

        case 'retorno_data':
            $dados['dia_hora_interesse'] = $message;
            $dados['tipo_atendimento'] = 'consulta_retorno';
            $dados['numero_contato'] = extrairNumeroCelular($chatId);
            
            salvarAgendamento($dados);

            // Enviar mensagem para outro número
            $mensagemRedirecionada = "Consulta Retorno:\n";
            $mensagemRedirecionada .= "Nome: " . $dados['nome_completo'] . "\n";
            $mensagemRedirecionada .= "Convênio: " . $dados['convenio'] . "\n";
            $mensagemRedirecionada .= "Nº Carteira: " . $dados['numero_carteira'] . "\n";
            $mensagemRedirecionada .= "Médico Preferência: " . $dados['medico_preferencia'] . "\n";
            $mensagemRedirecionada .= "Data/Hora Interesse: " . $dados['dia_hora_interesse'] . "\n";
            $mensagemRedirecionada .= "Contato: +" . $dados['numero_contato'];
            
            enviarParaOutroNumero('5521998362972', $mensagemRedirecionada);
            
            Model::resetChatState($chatId);
            return "Obrigado! Suas informações foram registradas com sucesso. Em breve entraremos em contato.";

        // Fluxo falta
        case 'falta_nome':
            $dados['nome_completo'] = $message;
            Model::updateChatState($chatId, 'falta_motivo', $dados);
            return "Por favor, informe o motivo da ausência:";

        case 'falta_motivo':
            $dados['motivo_ausencia'] = $message;
            Model::updateChatState($chatId, 'falta_hospital', $dados);
            return "Em caso de Internação, favor informar o hospital onde está internado:";

        case 'falta_hospital':
            $dados['hospital_internacao'] = $message;
            $dados['tipo_atendimento'] = 'falta';
            $dados['numero_contato'] = extrairNumeroCelular($chatId);
            
            salvarAgendamento($dados);

            // Enviar mensagem para outro número
            $mensagemRedirecionada = "Registro de Falta:\n";
            $mensagemRedirecionada .= "Nome: " . $dados['nome_completo'] . "\n";
            $mensagemRedirecionada .= "Motivo Ausência: " . $dados['motivo_ausencia'] . "\n";
            $mensagemRedirecionada .= "Hospital Internação: " . $dados['hospital_internacao'] . "\n";
            $mensagemRedirecionada .= "Contato: +" . $dados['numero_contato'];
            
            enviarParaOutroNumero('5521998362972', $mensagemRedirecionada);
            
            Model::resetChatState($chatId);
            return "Obrigado! Sua falta foi registrada com sucesso. Em breve entraremos em contato.";

        // Fluxo feedback
        case 'feedback':
            $dados['descricao_feedback'] = $message;
            $dados['tipo_atendimento'] = 'feedback';
            $dados['numero_contato'] = extrairNumeroCelular($chatId);
            $dados['nome_completo'] = explode('@', $chatId)[0];
            
            salvarAgendamento($dados);
            Model::resetChatState($chatId);
            return "Obrigado pelo seu feedback! Ele foi registrado com sucesso.\n\nA Nefroclinicas xxx agradece o contato.";
    }
    
    return "Desculpe, não entendi sua mensagem. Digite 'oi' para começar.";
}

// Função para verificar se é uma saudação
function isGreeting($message) {
    $greetings = ["oi", "olá", "ola", "bom dia", "boa tarde", "boa noite"];
    return in_array(strtolower($message), $greetings);
}

// Função para extrair apenas o número do celular do chatId
function extrairNumeroCelular($chatId) {
    // Remove tudo após o @ (incluindo o @)
    $numero = explode('@', $chatId)[0];
    return $numero;
}

try {
    // Recebimento do webhook com validação adicional
    $data = file_get_contents('php://input');
    
    // Log dos headers recebidos
    $headers = getallheaders();
    logError("Request Headers:", print_r($headers, true));
    
    // Log dos dados brutos
    logError("Webhook received", $data);
    
    // Validação inicial dos dados com mais detalhes
    $array = json_decode($data, true);
    if (empty($array) || !is_array($array)) {
        logError("Invalid webhook data received. JSON Error: " . json_last_error_msg());
        http_response_code(400);
        exit;
    }

    // Normaliza o formato da mensagem
    $normalizedArray = normalizeMessageFormat($array);
    
    // Se for uma mensagem de acknowledge, ignora o processamento
    if ($normalizedArray === null) {
        http_response_code(200);
        exit;
    }
    
    // Log da mensagem normalizada
    logError("Normalized message", json_encode($normalizedArray, JSON_PRETTY_PRINT));

    // Atualiza $array com a versão normalizada
    $array = $normalizedArray;

    // Extrair dados com validação
    $fromMe = isset($array["key"]["fromMe"]) ? $array["key"]["fromMe"] : false;
    $type = isset($array["messageType"]) ? $array["messageType"] : null;
    $message = extractMessage($array);
    $chatId = extractChatId($array);

    // Log dos dados processados
    logError("Processed message data", "chatId: $chatId, type: $type, message: " . ($message ?? 'null'));

    // Só processa mensagens de usuário (não do bot) e apenas tipos suportados
    if (!$fromMe && $chatId && $message && $type != "message.ack") {
        // Buscar estado atual do chat
        $currentState = Model::getChatState($chatId);
        
        // Processar a mensagem e obter resposta
        $response = processStep($chatId, $message, $currentState);
        
        // Enviar resposta
        if (!empty($response)) {
            Megaapi::text($chatId, $response);
        }
    } else {
        // Tratamento de status das mensagens enviadas
        if ($type == 'message.ack') {
            $instance = $array["instance_key"];
            $chatId = $array["key"]["remoteJid"];
            $idMessage = $array["key"]["id"];
            $status = replaceAck($array["update"]["status"]);

            switch ($status) {
                case '2':
                    $status = "Enviada";
                    break;
                case '3':
                    $status = "Entregue";
                    break;
                case '4':
                    $status = "Visualizada";
                    break;
                case '5':
                    $status = "Escutou o áudio / visualizou o vídeo";
                    break;
            }
        }
    }
} catch (Exception $e) {
    logError("Critical error: " . $e->getMessage());
    http_response_code(500);
    exit;
}
?>