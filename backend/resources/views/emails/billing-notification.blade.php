<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cobranca Tem de Tudo</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h2 style="margin-bottom: 12px;">{{ $subjectLabel }}</h2>

    <p>Ola, equipe <strong>{{ $companyName }}</strong>.</p>

    <p>
        Referente a fatura <strong>{{ $invoice->invoice_number }}</strong>,
        no valor de <strong>{{ $formattedTotal }}</strong>,
        com vencimento em <strong>{{ $dueDate }}</strong>.
    </p>

    @if(!empty($paymentUrl))
        <p>
            Link de pagamento: <a href="{{ $paymentUrl }}">{{ $paymentUrl }}</a>
        </p>
    @endif

    <p>
        Status atual: <strong>{{ strtoupper($invoice->status) }}</strong>.
    </p>

    <p style="margin-top: 24px;">
        Em caso de duvidas, responda este e-mail para o time de suporte.
    </p>

    <hr style="margin: 24px 0; border: 0; border-top: 1px solid #e5e7eb;">

    <p style="font-size: 12px; color: #6b7280;">
        Tem de Tudo - Plataforma de Fidelidade
    </p>
</body>
</html>

