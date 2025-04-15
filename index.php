<?php

require __DIR__ . '/vendor/autoload.php';

use core\Megaapi;
use core\Model;

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
                $numeroDestino = '5521991159846'; // Número da recepção
                break;
            case 'falta':
                $numeroDestino = '5521991159846'; // Número da enfermagem
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
    if (!isset($_SESSION['conversations'])) {
        $_SESSION['conversations'] = [];
    }
    if (!isset($_SESSION['conversations'][$chatId])) {
        $_SESSION['conversations'][$chatId] = [
            'menu' => 'main', // Indica em qual menu estamos: 'main', 'consulta', etc
            'fluxo' => null,
            'subfluxo' => null, // Novo campo para controlar subfluxos
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

    // Normaliza o campo key
    if (isset($array['key'])) {
        $normalized['key']['fromMe'] = !empty($array['key']['fromMe']);
        $normalized['key']['remoteJid'] = $array['key']['remoteJid'] ?? null;
        $normalized['key']['id'] = $array['key']['id'] ?? null;
    }

    // Normaliza o tipo de mensagem
    $normalized['messageType'] = $array['messageType'] ?? null;

    // Normaliza a mensagem
    if (isset($array['message'])) {
        if (isset($array['message']['conversation'])) {
            $normalized['message']['conversation'] = $array['message']['conversation'];
        } elseif (isset($array['message']['extendedTextMessage']['text'])) {
            $normalized['message']['conversation'] = $array['message']['extendedTextMessage']['text'];
        }
    }

    return $normalized;
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
    $array = normalizeMessageFormat($array);
    
    // Log da mensagem normalizada
    logError("Normalized message", json_encode($array, JSON_PRETTY_PRINT));

    // Extrair dados com validação
    $fromMe = isset($array["key"]["fromMe"]) ? $array["key"]["fromMe"] : false;
    $type = isset($array["messageType"]) ? $array["messageType"] : null;
    $message = extractMessage($array);
    $chatId = extractChatId($array);

    // Log dos dados processados
    logError("Processed message data", "chatId: $chatId, type: $type, message: $message");

    // Só processa mensagens de usuário (não do bot) e apenas tipos suportados
    if (!$fromMe && $chatId && $message && $type != "message.ack") {
        // Recupera o estado da conversa para este chat
        $state = getConversationState($chatId);
        
        // Log do estado atual para debug
        logError("Current state before processing", print_r($state, true));
        
        // --- FLUXO PRINCIPAL DO BOT ---
        if (in_array(strtolower($message), ["oi", "olá", "ola", "Oi", "Olá", "Ola", "Bom dia", "Boa tarde", "Boa noite", "bom dia", "boa tarde", "boa noite"])) {
            $state = [
                'menu' => 'main',
                'fluxo' => null,
                'subfluxo' => null,
                'dados' => [],
                'campo' => 0,
                'perguntas' => []
            ];
            updateConversationState($chatId, $state);
            Megaapi::text($chatId, "Seja bem-vindo à Nefroclinicas unidade xxx. Digite a opção desejada abaixo:\n\n1 - Marcação de Consultas\n2 - Solicitação de Vaga para Diálise\n3 - Notificar uma Falta ou Ausência de Sessão de Diálise\n4 - Caso queira deixar um elogio, sugestão ou reclamação");
            exit;
        }

        // --- MENU PRINCIPAL ---
        if ($state['menu'] === 'main' && $state['fluxo'] === null) {
            switch($message) {
                case "1":
                    $state['menu'] = 'consulta';
                    $state['fluxo'] = 'escolha_tipo';
                    updateConversationState($chatId, $state);
                    Megaapi::text($chatId, "Digite:\n1 - Para primeira consulta\n2 - Caso não seja sua primeira consulta conosco");
                    exit;
                case "2":
                    Megaapi::text($chatId, "Para mais informações de vagas em nossa unidade, segue o contato da nossa chefe de Captação: Cynthia - 21 98987-6009");
                    Megaapi::text($chatId, "A Nefroclinicas xxx agradece o contato.");
                    $state['menu'] = 'main';
                    $state['fluxo'] = null;
                    updateConversationState($chatId, $state);
                    exit;
                case "3":
                    $state['fluxo'] = 'falta';
                    $state['dados'] = [];
                    $state['campo'] = 0;
                    $perguntas = [
                        'Nome completo do Paciente:',
                        'Motivo da ausência:',
                        'Em caso de Internação, favor informar o hospital onde está internado:'
                    ];
                    $state['perguntas'] = $perguntas;
                    updateConversationState($chatId, $state);
                    Megaapi::text($chatId, $perguntas[0]);
                    exit;
                case "4":
                    $state['fluxo'] = 'feedback';
                    updateConversationState($chatId, $state);
                    Megaapi::text($chatId, "Por favor, descreva para nós o seu elogio, sugestão ou reclamação:");
                    exit;
                default:
                    Megaapi::text($chatId, "Desculpe, não entendi sua mensagem. Por favor, digite uma das opções válidas do menu.");
                    exit;
            }
        }

        // --- MENU DE CONSULTAS ---
        if ($state['menu'] === 'consulta' && $state['fluxo'] === 'escolha_tipo') {
            switch($message) {
                case "1":
                    $state['fluxo'] = 'primeira_consulta';
                    $state['subfluxo'] = 'coleta_dados';
                    $state['dados'] = [];
                    $state['campo'] = 0;
                    $perguntas = [
                        'Nome Completo:',
                        'Identidade:',
                        'CPF:',
                        'Endereço:',
                        'Convênio:',
                        'Número da Carteira do Convênio:',
                        'Médico de preferência (se houver):',
                        'Dia e hora de interesse:'
                    ];
                    $state['perguntas'] = $perguntas;
                    updateConversationState($chatId, $state);
                    Megaapi::text($chatId, $perguntas[0]);
                    exit;
                case "2":
                    $state['fluxo'] = 'consulta_retorno';
                    $state['subfluxo'] = 'coleta_dados';
                    $state['dados'] = [];
                    $state['campo'] = 0;
                    $perguntas = [
                        'Nome Completo:',
                        'Convênio:',
                        'Número da Carteira do Convênio:',
                        'Médico de preferência (se houver):',
                        'Dia e hora de interesse:'
                    ];
                    $state['perguntas'] = $perguntas;
                    updateConversationState($chatId, $state);
                    Megaapi::text($chatId, $perguntas[0]);
                    exit;
                default:
                    Megaapi::text($chatId, "Opção inválida. Digite 1 para primeira consulta ou 2 para retorno.");
                    exit;
            }
        }

        // --- PROCESSAMENTO DOS FLUXOS ---
        if (in_array($state['fluxo'], ['primeira_consulta', 'consulta_retorno']) && $state['subfluxo'] === 'coleta_dados') {
            $perguntas = $state['perguntas'];
            $campo = $state['campo'];
            $state['dados'][] = $message;
            $campo++;
            
            if ($campo < count($perguntas)) {
                $state['campo'] = $campo;
                updateConversationState($chatId, $state);
                Megaapi::text($chatId, $perguntas[$campo]);
                exit;
            } else {
                $campos = $state['fluxo'] === 'primeira_consulta'
                    ? ['nome_completo','identidade','cpf','endereco','convenio','numero_carteira','medico_preferencia','dia_hora_interesse']
                    : ['nome_completo','convenio','numero_carteira','medico_preferencia','dia_hora_interesse'];
                
                $dadosAssoc = array_combine($campos, $state['dados']);
                $dadosAssoc['tipo_atendimento'] = $state['fluxo'];
                $numeroContato = isset($array['key']['remoteJid']) ? explode('@', $array['key']['remoteJid'])[0] : null;
                $dadosAssoc['numero_contato'] = $numeroContato;
                
                salvarAgendamento($dadosAssoc);
                Megaapi::text($chatId, "Obrigado! Suas informações foram registradas com sucesso. Em breve entraremos em contato.");
                
                // Reset do estado
                $state = [
                    'menu' => 'main',
                    'fluxo' => null,
                    'subfluxo' => null,
                    'dados' => [],
                    'campo' => 0,
                    'perguntas' => []
                ];
                updateConversationState($chatId, $state);
                exit;
            }
        }

        if ($state['fluxo'] === 'falta') {
            $perguntas = $state['perguntas'];
            $campo = $state['campo'];
            $state['dados'][] = $message;
            $campo++;
            if ($campo < count($perguntas)) {
                $state['campo'] = $campo;
                updateConversationState($chatId, $state);
                Megaapi::text($chatId, $perguntas[$campo]);
                exit;
            } else {
                $campos = ['nome_completo','motivo_ausencia','hospital_internacao'];
                $dadosAssoc = array_combine($campos, $state['dados']);
                $dadosAssoc['tipo_atendimento'] = 'falta';
                // Salva o número de contato
                $numeroContato = isset($array['key']['remoteJid']) ? explode('@', $array['key']['remoteJid'])[0] : null;
                $dadosAssoc['numero_contato'] = $numeroContato;
                salvarAgendamento($dadosAssoc);
                Megaapi::text($chatId, "Obrigado! Sua falta foi registrada com sucesso. Em breve entraremos em contato.");
                $state = [
                    'menu' => 'main',
                    'fluxo' => null,
                    'subfluxo' => null,
                    'dados' => [],
                    'campo' => 0,
                    'perguntas' => []
                ];
                updateConversationState($chatId, $state);
                exit;
            }
        }

        if ($state['fluxo'] === 'feedback') {
            // Pega o nome do WhatsApp (pushName) ou o número do contato
            $nomeContato = null;
            if (isset($array['pushName']) && !empty($array['pushName'])) {
                $nomeContato = $array['pushName'];
            } else if (isset($array['key']['remoteJid'])) {
                $jid = $array['key']['remoteJid'];
                $nomeContato = explode('@', $jid)[0];
            }
            $numeroContato = isset($array['key']['remoteJid']) ? explode('@', $array['key']['remoteJid'])[0] : null;
            $dadosAssoc = [
                'nome_completo' => $nomeContato,
                // 'nome_contato' => $numeroContato, // salva também em nome_contato
                'descricao_feedback' => $message,
                'tipo_atendimento' => 'feedback',
                'numero_contato' => $numeroContato
            ];
            salvarAgendamento($dadosAssoc);
            Megaapi::text($chatId, "Obrigado pelo seu feedback! Ele foi registrado com sucesso.");
            Megaapi::text($chatId, "A Nefroclinicas xxx agradece o contato.");
            $state = [
                'menu' => 'main',
                'fluxo' => null,
                'subfluxo' => null,
                'dados' => [],
                'campo' => 0,
                'perguntas' => []
            ];
            updateConversationState($chatId, $state);
            exit;
        }

        // --- MENSAGEM DE ERRO PADRÃO ---
        Megaapi::text($chatId, "Desculpe, não entendi sua mensagem. Por favor, digite uma das opções válidas do menu.");
        exit;
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