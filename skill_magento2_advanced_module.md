# Skill: Projektowanie i Tworzenie Zaawansowanych Modułów Magento 2 (Backend, Frontend, API)

## 1. CEL I ZASTOSOWANIE (Context & Purpose)
Ten uniwersalny wzorzec (Skill) służy do tworzenia i refaktoryzacji **dowolnych modułów Magento 2**, niezależnie od tego, czy pełnią funkcję czysto backendową (integracje ERP, zarządzanie zamówieniami), frontendową (nowe widoki, kasy, katalogi) czy headless (API, GraphQL).

Wzorzec rozwiązuje problem niestabilnego, podatnego na błędy i trudnego w utrzymaniu kodu ("spaghetti code"), w którym logika miesza się z widokami lub kontrolerami. Stosowanie tego Skilla wymusza standard "Enterprise Grade", gwarantując bezpieczeństwo, skalowalność, wysoką wydajność oraz bezproblemową współpracę z nowoczesnymi frontendami (takimi jak Hyvä Themes).

---

## 2. ARCHITEKTURA I STRUKTURA PLIKÓW (Directory Structure)
Struktura opiera się na separacji ról (Separation of Concerns). Logika biznesowa jest odseparowana od prezentacji.

```text
Vendor_Module/
├── Api/                          # Service Contracts (Główna umowa modułu)
│   ├── Data/                     # Interfejsy DTO i encji (np. EntityInterface)
│   ├── ManagementInterface.php   # Akcje biznesowe (procesy, integracje)
│   └── RepositoryInterface.php   # Operacje CRUD i wyszukiwanie (SearchCriteria)
├── Block/                        # Bloki wstecznie kompatybilne (Luma), z lazy-loadingiem
├── ViewModel/                    # (Preferowane) Klasy widoku dla frontendów Hyvä/Luma
├── Controller/                   
│   ├── Adminhtml/                # Kontrolery panelu admina (chronione przez precyzyjne ACL)
│   └── Index/                    # Kontrolery frontendu (tylko wpuszczanie requestu i delegacja)
├── Cron/                         # Zadania cykliczne (np. auto-anulowanie po N dniach)
├── Model/
│   ├── Config.php                # Centralny punkt pobierania ustawień (ScopeConfig)
│   ├── StatusValidator.php       # Maszyna stanów — tabela ALLOWED_TRANSITIONS (Single Source of Truth)
│   ├── Resolver/                 # Resolvery GraphQL i klasy Cache Identity
│   ├── ResourceModel/            # Dostęp do bazy (Resource, Collection)
│   └── Service/                  # Implementacje Service Contracts
├── Observer/                     # Nasłuchiwanie na natywne lub własne eventy (np. wysyłka e-mail)
├── Plugin/                       # Przechwytywanie i modyfikowanie zachowań klas publicznych (Interceptors)
├── Setup/Patch/Data/             # Patche danych (np. InstallDefaultReasons)
├── Test/Unit/                    # Testy jednostkowe PHPUnit bez frameworka Magento
├── Ui/                           # Data Providers dla Ui Components (Gridy, Formularze Admina)
├── etc/
│   ├── acl.xml                   # Granularne uprawnienia ról (odrębne dla list, akcji, edycji)
│   ├── adminhtml/routes.xml & menu.xml
│   ├── config.xml                # Domyślne wartości konfiguracyjne (zawsze definiuj!)
│   ├── crontab.xml               # Definicja zadania CRON (np. o 02:00)
│   ├── db_schema.xml             # Deklaratywny schemat bazy z odpowiednimi indexami (złożonymi)
│   ├── db_schema_whitelist.json  # Whitelist kolumn/kluczy (wymagana po setup:db-declaration:generate-whitelist)
│   ├── di.xml                    # Bindowanie interfejsów, konfiguracja argumentów, pluginy
│   ├── email_templates.xml       # Rejestracja szablonów e-mail HTML
│   ├── events.xml                # Definicje nasłuchiwanych eventów
│   └── schema.graphqls           # Typy, Queries i Mutations dla Headless
└── view/
    ├── adminhtml/
    │   └── ui_component/         # Gridy (Listing) i formularze w panelu admina
    └── frontend/
        ├── email/                # Responsywne szablony e-mail (.html)
        ├── layout/               # Definicje układu stron
        ├── templates/            # Szablony .phtml (pisane z myślą o ViewModelach)
        └── web/                  # Zasoby: Alpine.js/Tailwind (Hyvä) lub RequireJS/Knockout (Luma)
```

---

## 3. KLUCZOWE KOMPONENTY I LOGIKA (Core Logic & Snippets)

### A. Frontend: Routing i Layouty (Krytyczna Pułapka)

> **UWAGA:** Layouty w Magento są mapowane na podstawie hierarchii `routeId_kontroler_akcja`. Jeśli kontrolery frontendu dla ścieżki `/rma/*` są umieszczone w katalogu `Controller/Customer/` zamiast `Controller/Index/`, layouty takie jak `kkkonrad_rma_index_view.xml` nie zostaną odnalezione i strona załaduje się **bez layoutu** (404 lub pusty content).

**Reguła:** Zawsze umieszczaj kontrolery frontendu w katalogu odpowiadającym segmentowi URL:
- URL: `/rma/index/view` → Kontroler: `Controller/Index/View.php`
- URL: `/rma/index/index` → Kontroler: `Controller/Index/Index.php`
- URL: `/rma/index/printslip` → Kontroler: `Controller/Index/PrintSlip.php`

### B. Service Layer (Brak logiki biznesowej w kontrolerach)
Kontrolery nie mogą zawierać zapytań SQL ani złożonej logiki. Ich jedynym celem jest odebranie requestu, przekazanie go do `ManagementInterface` i zwrócenie wyniku (`Redirect/JSON/Page`). Wszystkie akcje biznesowe są metodami w klasie implementującej `ManagementInterface`.

*Boilerplate Kontrolera:*
```php
public function execute(): ResultInterface
{
    $resultRedirect = $this->resultRedirectFactory->create();
    $entityId = (int) $this->getRequest()->getParam('id');
    try {
        $this->managementService->processAction($entityId);
        $this->messageManager->addSuccessMessage(__('Action successful.'));
    } catch (LocalizedException $e) {
        $this->messageManager->addErrorMessage($e->getMessage());
    } catch (\Exception $e) {
        $this->logger->error('Error processing ID ' . $entityId . ': ' . $e->getMessage());
        $this->messageManager->addErrorMessage(__('An error occurred.'));
    }
    return $resultRedirect->setPath('*/*/index');
}
```

*Boilerplate Kontrolera Adminhtml (z ACL):*
```php
class Index extends Action
{
    // Precyzyjny zasób ACL — nie używaj nadrzędnego, np. Vendor_Module::module
    public const ADMIN_RESOURCE = 'Vendor_Module::entity_list';

    public function execute(): \Magento\Framework\View\Result\Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Vendor_Module::entity_list');
        $resultPage->addBreadcrumb(__('Entity'), __('Entity'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Entities'));
        return $resultPage;
    }
}
```

### C. Maszyna Stanów (State Machine Validator)
Dla encji o złożonym cyklu życia (zamówienia, zwroty, tickety) nigdy nie sprawdzaj statusów w if/else wewnątrz kontrolerów. Zamiast tego utwórz dedykowaną klasę `StatusValidator` ze statyczną tablicą przejść `ALLOWED_TRANSITIONS`:

```php
class StatusValidator
{
    private const ALLOWED_TRANSITIONS = [
        'new'            => ['pending_review', 'cancelled'],
        'pending_review' => ['approved', 'rejected', 'cancelled'],
        'approved'       => ['item_in_transit', 'cancelled'],
        'rejected'       => ['closed'],
        'item_in_transit'=> ['item_received'],
        'item_received'  => ['resolved', 'rejected'],
        'resolved'       => ['closed'],
        'closed'         => [],   // terminal — brak przejść
        'cancelled'      => [],   // terminal — brak przejść
    ];

    /** @throws LocalizedException */
    public function validate(string $from, string $to): void
    {
        if (!in_array($to, self::ALLOWED_TRANSITIONS[$from] ?? [], true)) {
            throw new LocalizedException(
                __('Cannot transition from "%1" to "%2".', $from, $to)
            );
        }
    }

    public function isTerminalStatus(string $status): bool
    {
        return isset(self::ALLOWED_TRANSITIONS[$status])
            && empty(self::ALLOWED_TRANSITIONS[$status]);
    }
}
```

Klasa ta jest idealna do testowania jednostkowego bez mockowania frameworka Magento.

### D. Repository Pattern (CRUD z SearchCriteria)
```php
class EntityRepository implements EntityRepositoryInterface
{
    public function save(EntityInterface $entity): EntityInterface
    {
        try {
            $this->resource->save($entity);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save: %1', $e->getMessage()), $e);
        }
        return $entity;
    }

    public function getById(int $id): EntityInterface
    {
        $entity = $this->entityFactory->create();
        $this->resource->load($entity, $id);
        if (!$entity->getId()) {
            throw new NoSuchEntityException(__('Entity with ID "%1" does not exist.', $id));
        }
        return $entity;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        $results = $this->searchResultsFactory->create();
        $results->setSearchCriteria($searchCriteria);
        $results->setItems($collection->getItems());
        $results->setTotalCount($collection->getSize());
        return $results;
    }

    /**
     * BEZPIECZEŃSTWO: filtr customer_id ZAWSZE po process(), aby nadpisał
     * ewentualny filtr wstrzyknięty przez klienta przez SearchCriteria (IDOR)
     */
    public function getListForCustomer(int $customerId, SearchCriteriaInterface $sc): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($sc, $collection);
        $collection->addFieldToFilter('customer_id', ['eq' => $customerId]); // po process()!
        // ... reszta jak wyżej
    }
}
```

### E. Dispatcher Eventów + Observer do e-maili
Logika rdzenną (np. `RmaManagement`) emituje event zamiast wysyłać e-mail bezpośrednio. Observer odpowiada za wysyłkę — nie obciąża logiki głównej:

*W ManagementInterface:*
```php
$this->eventManager->dispatch('vendor_module_entity_created', [
    'entity' => $entity,
    'order'  => $order,
]);
```

*Observer (plik `Observer/SendEntityCreatedEmail.php`):*
```php
class SendEntityCreatedEmail implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $entity = $observer->getData('entity');
        if (!$entity || !$this->config->isEnabled($entity->getStoreId())) {
            return;
        }
        try {
            $this->inlineTranslation->suspend();
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($this->config->getCreatedEmailTemplate($entity->getStoreId()))
                ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $entity->getStoreId()])
                ->setTemplateVars(['entity' => $entity, 'store' => $this->storeManager->getStore($entity->getStoreId())])
                ->setFromByScope($this->config->getEmailSender($entity->getStoreId()))
                ->addTo($entity->getCustomerEmail(), $entity->getCustomerName())
                ->getTransport();
            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error('Email send failed: ' . $e->getMessage());
        } finally {
            $this->inlineTranslation->resume(); // ZAWSZE w finally
        }
    }
}
```

### F. Zabezpieczanie Plików (Upload z walidacją MIME)
Nigdy nie ufaj rozszerzeniu pliku. Zawsze waliduj typ MIME przez funkcję serwerową `finfo`:

```php
$finfo    = new \finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($_FILES['file']['tmp_name']);
if ($mimeType !== 'application/pdf') {
    throw new LocalizedException(__('Invalid file type.'));
}
// Generuj bezpieczną losową nazwę pliku
$safeFileName = $this->random->getUniqueHash() . '.pdf';
// Ścieżka relatywna do media dir
$mediaDir->copyFile($_FILES['file']['tmp_name'], 'vendor/module/' . $safeFileName);
```

### G. Zadania CRON z odpornością na błędy
Każda iteracja w cronie musi być opakowana osobnym `try/catch`:

```php
public function execute(): void
{
    $days = $this->config->getAutoCancelDays();
    if ($days <= 0) return; // Respektuj konfigurację admina

    $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    $collection = $this->collectionFactory->create()
        ->addFieldToFilter('status', ['eq' => 'pending_review'])
        ->addFieldToFilter('updated_at', ['lteq' => $cutoff]);

    $done = 0; $errors = 0;
    foreach ($collection as $item) {
        try {
            $this->management->cancel((int) $item->getId(), 'Auto-cancelled.');
            $done++;
        } catch (\Exception $e) {
            $errors++;
            $this->logger->error("Cron: failed to cancel ID {$item->getId()}: {$e->getMessage()}");
        }
    }
    if ($done || $errors) {
        $this->logger->info("Cron: done={$done}, errors={$errors}");
    }
}
```

### H. GraphQL — Query + Mutation z autoryzacją klienta

*schema.graphqls:*
```graphql
type Query {
    customerEntities(pageSize: Int = 10, currentPage: Int = 1): CustomerEntityOutput
        @resolver(class: "Vendor\\Module\\Model\\Resolver\\CustomerEntityList")
        @cache(cacheIdentity: "Vendor\\Module\\Model\\Resolver\\Cache\\CustomerEntityListIdentity")

    rmaReasons: [RmaReasonOption!]!
        @resolver(class: "Vendor\\Module\\Model\\Resolver\\RmaReasons")
}

type Mutation {
    createCustomerEntity(input: CreateCustomerEntityInput!): CreateCustomerEntityOutput
        @resolver(class: "Vendor\\Module\\Model\\Resolver\\CreateCustomerEntity")

    addCustomerEntityMessage(input: AddMessageInput!): AddMessageOutput
        @resolver(class: "Vendor\\Module\\Model\\Resolver\\AddCustomerEntityMessage")
}
```

*Resolver klienta (z autoryzacją):*
```php
public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
{
    if (false === $context->getExtensionAttributes()->getIsCustomer()) {
        throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
    }
    $customerId = (int) $context->getUserId();
    // ... reszta logiki, odwołanie przez Management lub Repository
}
```

---

## 4. DOBRE PRAKTYKI I OPTYMALIZACJA (Best Practices & Performance)

1. **Hyvä Themes vs Luma:**
   Jeśli moduł posiada frontend, używaj `Alpine.js` i `TailwindCSS` (dla Hyvä) zamiast `RequireJS`, `jQuery` i `Knockout.js` (Luma). Escape'uj dane w szablonach (`$escaper->escapeHtml()` / `escapeUrl()`). Dla własnych linków w nawigacji konta klienta użyj osobnego szablonu `.phtml` z ikoną SVG i klasami Tailwind.

2. **Optymalizacja Wydajności (Unikanie N+1):**
   W `ViewModels` i `Blocks` (lub GraphQL Resolvers) cache'uj pobrane kolekcje wewnątrz zmiennej instancji, by kolejne wywołanie metody z szablonu nie obciążało bazy:
   ```php
   private ?array $cachedItems = null;
   public function getItems(): array
   {
       return $this->cachedItems ??= $this->loadItemsFromDb();
   }
   ```

3. **Niezawodne Mass Actions w Adminie:**
   Nigdy nie przerywaj głównej pętli, gdy jeden z elementów rzuci wyjątek. Otaczaj operacje na pojedynczym elemencie blokiem `try/catch (\Exception $e)` i loguj to przez wstrzyknięty `Psr\Log\LoggerInterface` wymieniając konkretne ID, które sprawiło problem. Zliczaj sukcesy i błędy, informuj admina zbiorczym komunikatem.

4. **Zoptymalizowane Zapytania i Baza Danych:**
   Twórz **indeksy złożone** w `db_schema.xml` dla zapytań (np. Cron), które filtrują równocześnie po kilku kolumnach:
   ```xml
   <!-- Composite index for cron: WHERE status=? AND updated_at<=? -->
   <index referenceId="VENDOR_ENTITY_STATUS_UPDATED_AT" indexType="btree">
       <column name="status"/>
       <column name="updated_at"/>
   </index>
   ```
   Po każdej zmianie `db_schema.xml` uruchom:
   ```bash
   php bin/magento setup:db-declaration:generate-whitelist --module-name=Vendor_Module
   php bin/magento setup:upgrade
   ```

5. **Config Model i Puste Multiselecty:**
   W modelach `Config.php`, pobierając wartości wielokrotnego wyboru (multiselect), pamiętaj, że gdy admin odznaczy wszystko, Magento zwróci pusty string (`''`), a gdy pole jest w ogóle niezdefiniowane, zwróci `null`. Sprawdzaj przez `if (!$value)` aby prawidłowo fallbackować do wartości domyślnej:
   ```php
   public function getAllowedOrderStatuses(?int $storeId = null): array
   {
       $value = $this->scopeConfig->getValue(self::XML_PATH_STATUSES, ScopeInterface::SCOPE_STORE, $storeId);
       if (!$value) {
           return ['complete']; // fallback — brak wyboru = tylko zamówienia complete
       }
       return array_filter(array_map('trim', explode(',', (string) $value)));
   }
   ```

6. **Zawsze `config.xml` z wartościami domyślnymi:**
   Po dodaniu pola do `system.xml` zdefiniuj jego wartość domyślną w `etc/config.xml`, inaczej moduł będzie zwracał `null` zamiast rozsądnej wartości do pierwszego zapisu przez admina.
   ```xml
   <default>
       <vendor_module>
           <email>
               <created_template>vendor_module_email_entity_created</created_template>
               <sender>general</sender>
           </email>
           <general>
               <return_window_days>30</return_window_days>
           </general>
       </vendor_module>
   </default>
   ```

7. **Wydruk Widoku (layout="print"):**
   Dla widoków do druku (np. slip zwrotny) używaj dedykowanego kontrolera i layout XML z atrybutem `layout="print"`. Automatyczne wywołanie okna druku — `window.print()` — umieszczone w `<script>` na końcu szablonu zapewni natychmiastowe wydrukowanie po załadowaniu strony.

8. **Race Condition Guard — deduplikacja aktywnych rekordów:**
   Przed tworzeniem nowego rekordu (np. RMA dla zamówienia) sprawdź, czy dla tego samego zamówienia/klienta nie istnieje już aktywny rekord w stanie innym niż terminal. Zapobiega to powstawaniu duplikatów przy szybkim podwójnym kliknięciu.

---

## 5. KONFIGURACJA STATYCZNEJ ANALIZY KODU (PHPStan dla Magento 2)

Konfiguracja `phpstan.neon` wymagana do poprawnego działania analizy w module Magento 2:

```yaml
parameters:
    level: 2
    paths:
        - .
    scanDirectories:
        # Kluczowe: pozwala PHPStan rozpoznać wygenerowane fabryki (np. RmaFactory)
        # Bez tego: 118+ błędów "Class RmaFactory not found"
        - ../../../../generated/code/Vendor/Module
    excludePaths:
        analyse:
            - Test/*
            - vendor/*
    bootstrapFiles:
        - ../../../../dev/tests/static/framework/autoload.php
    ignoreErrors:
        # Ignoruj magiczne gettery/settery Magento modeli (DataObject)
        - '#Call to an undefined method [a-zA-Z0-9\\_]+::get[a-zA-Z0-9_]+\(\)#'
        - '#Call to an undefined method [a-zA-Z0-9\\_]+::set[a-zA-Z0-9_]+\(\)#'
```

Uruchamianie:
```bash
./vendor/bin/phpstan analyse -c app/code/Vendor/Module/phpstan.neon app/code/Vendor/Module/ --memory-limit=1G
```

---

## 6. TESTY JEDNOSTKOWE (Unit Tests — PHPUnit bez frameworka Magento)

Klasy czysto logiczne (np. `StatusValidator`, `Config`) można testować bez mockowania frameworka:

```php
class StatusValidatorTest extends TestCase
{
    private StatusValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new StatusValidator(); // Bez mockowania!
    }

    /** @dataProvider validTransitionsProvider */
    public function testValidTransitions(string $from, string $to): void
    {
        $this->validator->validate($from, $to); // Brak wyjątku = sukces
        $this->assertTrue(true);
    }

    public function validTransitionsProvider(): array
    {
        return [
            'new → pending_review' => ['new', 'pending_review'],
            'approved → transit'   => ['approved', 'item_in_transit'],
        ];
    }

    /** @dataProvider invalidTransitionsProvider */
    public function testInvalidTransitionsThrowException(string $from, string $to): void
    {
        $this->expectException(LocalizedException::class);
        $this->validator->validate($from, $to);
    }
}
```

Uruchamianie:
```bash
./vendor/bin/phpunit app/code/Vendor/Module/Test/Unit/
```

---

## 7. TYPOWE PUŁAPKI I GOTOWE ROZWIĄZANIA (Gotchas & Fixes)

| Problem | Rozwiązanie |
|---------|-------------|
| **404 na froncie** mimo poprawnego `routes.xml` | Sprawdź czy katalog kontrolera odpowiada segmentowi URL: `/rma/index/view` → `Controller/Index/View.php`, nie `Controller/Customer/View.php` |
| **`TypeError: execute() must return ResultInterface`** | Kontroler adminhtml musi zwracać `ResultInterface`. Upewnij się, że wszystkie ścieżki kodu (`if/else`, `try/catch`) zwracają obiekt `$resultRedirect` lub `$resultPage`, nigdy `null` / `void`. |
| **Wygenerowane fabryki niewidoczne dla PHPStan** | Dodaj `scanDirectories` w `phpstan.neon` wskazujące na `generated/code/Vendor/Module`. |
| **Email nie idzie do klienta** | Zawsze wywołuj `$this->inlineTranslation->suspend()` przed buildem i `->resume()` w bloku `finally`. |
| **Duplikat RMA dla tego samego zamówienia** | Sprawdź przez `SearchCriteriaBuilder` + `getList()` czy nie istnieje aktywny rekord przed zapisem. |
| **Multiselect zwraca `null` po `setup:upgrade`** | Dodaj wartość domyślną w `etc/config.xml`; sprawdzaj `!$value` zamiast `$value === null`. |
| **Indeks złożony nie istnieje** | Po modyfikacji `db_schema.xml` uruchom `setup:db-declaration:generate-whitelist` PRZED `setup:upgrade`. |
| **PDF upload akceptuje fałszywy plik** | Waliduj przez `new \finfo(FILEINFO_MIME_TYPE)->file($tmpName)`, nie przez `$_FILES['type']` ani rozszerzenie. |
| **IDOR — klient widzi dane innego klienta** | Filtr `->addFieldToFilter('customer_id', $id)` **zawsze po** `collectionProcessor->process()`. |

---

## 8. PROMPT GENERATYWNY (Execution Prompt)

Poniżej znajduje się prompt, który służy jako uniwersalna instrukcja inicjująca przy generowaniu lub refaktoryzowaniu KAŻDEGO modułu Magento 2 przez LLM (zarówno backend, frontend, jak i API).

```text
Działaj jako Główny Architekt i Senior Developer Magento 2. 
Zadanie: Wygeneruj (lub zrefaktoryzuj) moduł [NAZWA_MODULU] realizujący następującą funkcję biznesową: [OPIS_FUNKCJI].

Przed przystąpieniem do pisania kodu, zapytaj mnie o następujące rzeczy (jeśli nie podałem ich wyżej):
1. Jaki stos frontendowy wykorzystujemy? (Brak frontendu / Hyvä Themes / Luma+Knockout / PWA).
2. Czy moduł ma eksponować funkcjonalności przez GraphQL lub REST API?

W generowanym kodzie bezwzględnie przestrzegaj poniższych reguł architektonicznych (Enterprise Grade):

1. **Architektura Service Contracts i ViewModels:** 
   Logika biznesowa musi być w całości umieszczona w warstwie Service (ManagementInterface) i zarządzana przez RepositoryInterface. Kontrolery (HTTP) służą wyłącznie jako routery: łapią parametry i przekazują dalej. W przypadku frontendu logikę prezentacyjną umieszczaj w `ViewModel`, które są przekazywane do szablonów .phtml, unikając dziedziczenia po ciężkich klasach `Block`.

2. **Routing i layouty frontendu:**
   Kontrolery frontendu ZAWSZE umieszczaj w katalogu odpowiadającym segmentowi URL. Dla trasy `/routeId/controller/action` kontroler musi być w `Controller/Controller/Action.php`. Błędny katalog (np. `Controller/Customer/`) spowoduje nieładowanie layoutów i efekt 404.

3. **Bezpieczeństwo (IDOR, Mass Assignment, Pliki):**
   Jeśli zapytanie dotyczy zasobów konkretnego klienta (Frontend / GraphQL), nakładaj filtr `$collection->addFieldToFilter('customer_id', $id)` zawsze **PO** wywołaniu `collectionProcessor->process(...)`, by zapobiec atakom polegającym na nadpisywaniu filtrów w SearchCriteria. Wgrywane pliki weryfikuj zawsze funkcją serwerową `finfo` (MIME-type), nigdy nie ufaj rozszerzeniom i superglobalnej `$_FILES`. Używaj `$escaper` w szablonach HTML.

4. **Maszyna stanów (State Machine):**
   Dla encji ze złożonym cyklem życia utwórz dedykowaną klasę `StatusValidator` ze statyczną tablicą `ALLOWED_TRANSITIONS`. Jest ona idealnym kandydatem do testów jednostkowych bez mockowania frameworka Magento.

5. **Wydajność bazy i ochrona przed zjawiskiem N+1:**
   Wszelkie metody ładujące dane do widoku (np. zagnieżdżone elementy w ViewModelu lub GraphQL Resolverze) muszą cache'ować wyniki wewnątrz instancji klasy przy użyciu lazy-loadingu (`$this->cached ??= $this->load()`). Skrypty cykliczne i skomplikowane kolekcje filtrujące po wielu kolumnach wymagają stworzenia dla nich **indeksów złożonych** w `db_schema.xml`.

6. **Odseparowanie akcji pobocznych (Events / Observers):**
   Akcje takie jak wysyłka e-mail, logowanie audytowe lub asynchroniczna komunikacja zewnętrzna nie mogą obciążać logiki rdzennej. Dysponuj eventem (`eventManager->dispatch()`), nasłuchuj za pomocą `ObserverInterface`. Do e-maili używaj `TransportBuilder` i pauzuj lokalizację (`inlineTranslation->suspend()` / `->resume()` w `finally`).

7. **Niezawodność i logowanie wyjątków w Adminie / Mass Actions:**
   Dodawaj precyzyjne zasoby ACL w `acl.xml` (nie grupuj wszystkiego pod głównym resource, daj np. uprawnienie tylko do akceptacji, usunięcia, wglądu). W Mass Action'ach kontrolerów (np. MassDelete, MassApprove) ZAWSZE wyłapuj `\Exception` w pojedynczym obrocie pętli i loguj jego treść z ID wykraczającej encji poprzez wstrzyknięte `Psr\Log\LoggerInterface`. Nie pozwól, aby błąd na jednym elemencie zatrzymał wykonanie pozostałych operacji.

8. **Konfiguracja Store i metody statyczne:**
   Nie twórz metod statycznych w celu obejścia Dependency Injection. Używaj dedykowanego modelu `Config.php` do zaczytywania wszelkich zmiennych konfiguracyjnych ze ScopeConfig. Zawsze definiuj wartości domyślne w `etc/config.xml`. Zwracaj szczególną uwagę na rozróżnianie pustego wyboru multiselectu (`$value === ''`) od całkowitego braku ustawienia z fallbackiem do wartości domyślnej (`$value === null`).

9. **Analiza statyczna (PHPStan):**
   Skonfiguruj `phpstan.neon` ze `scanDirectories` wskazującym na `generated/code/Vendor/Module`, aby PHPStan rozpoznawał wygenerowane fabryki. Używaj reguł `ignoreErrors` dla magicznych getterów/setterów DataObject Magento.

Proszę, rozpocznij pracę od przygotowania kompletnej struktury katalogów dopasowanej do mojego stacku oraz omówienia w punktach jak zrealizujesz moje wymagania według tych zasad. Następnie poczekaj na moją akceptację przed wygenerowaniem faktycznego kodu.
```
