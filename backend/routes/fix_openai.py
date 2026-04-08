with open('api.php', 'rb') as f:
    content = f.read().decode('utf-8')

start = content.find('// Rotas do sistema de descontos')
openai_start = content.find('// Rotas OpenAI', start)
idx = openai_start
for i in range(2):
    idx = content.find('});', idx) + 3

new_block = """// Rotas do sistema de descontos
Route::prefix('discounts')->group(function () {
    // Consultar descontos disponiveis (publico - usuarios logados)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/company/{empresa_id}', [DiscountController::class, 'getCompanyDiscountLevels']);
        Route::post('/calculate', [DiscountController::class, 'calculateUserDiscount']);
        Route::post('/apply', [DiscountController::class, 'applyDiscount']);
    });

    // Rotas administrativas de descontos
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/configure', [DiscountController::class, 'configureCompanyDiscounts'])
            ->middleware(['admin.permission:manage_discounts']);
        Route::post('/find-customer', [DiscountController::class, 'findCustomerForDiscount'])
            ->middleware(['admin.permission:manage_discounts']);
    });
});

// Rotas OpenAI (admin apenas) - separadas do prefix discounts
Route::prefix('openai')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/status', [OpenAIController::class, 'status']);
    Route::get('/test', [OpenAIController::class, 'test'])
        ->middleware(['admin.permission:manage_system']);
    Route::post('/chat', [OpenAIController::class, 'chat'])
        ->middleware(['admin.permission:manage_system']);
    Route::post('/suggest', [OpenAIController::class, 'suggest'])
        ->middleware(['admin.permission:manage_system']);
});"""

new_content = content[:start] + new_block + content[idx:]
with open('api.php', 'wb') as f:
    f.write(new_content.encode('utf-8'))
print('DONE. New block written.')
verify = new_content.find('// Rotas OpenAI')
print(new_content[verify:verify+300])
