# Skill: Projektowanie i Tworzenie Zaawansowanych Modułów Magento 2 (Backend, Frontend, API)

## 1. CEL I ZASTOSOWANIE (Context & Purpose)
Ten uniwersalny wzorzec (Skill) służy do tworzenia i refaktoryzacji **dowolnych modułów Magento 2**, niezależnie od tego, czy pełnią funkcję czysto backendową (integracje ERP, zarządzanie zamówieniami), frontendową (nowe widoki, kasy, katalogi) czy headless (API, GraphQL).

Wzorzec rozwiązuje problem niestabilnego, podatnego na błędy i trudnego w utrzymaniu kodu ("spaghetti code"), w którym logika miesza się z widokami lub kontrolerami. Stosowanie tego Skilla wymusza standard "Enterprise Grade", gwarantując bezpieczeństwo, skalowalność, wysoką wydajność oraz bezproblemową współpracę z nowoczesnymi frontendami (takimi jak Hyvä Themes).

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
├── Model/
│   ├── Config.php                # Centralny punkt pobierania ustawień (ScopeConfig)
│   ├── Resolver/                 # Resolvery GraphQL i klasy Cache Identity
│   ├── ResourceModel/            # Dostęp do bazy (Resource, Collection)
│   └── Service/                  # Implementacje Service Contracts
├── Observer/                     # Nasłuchiwanie na natywne lub własne eventy (np. wysyłka e-mail)
├── Plugin/                       # Przechwytywanie i modyfikowanie zachowań klas publicznych (Interceptors)
├── Ui/                           # Data Providers dla Ui Components (Gridy, Formularze Admina)
├── etc/
│   ├── acl.xml                   # Granularne uprawnienia ról (odrębne dla list, akcji, edycji)
│   ├── adminhtml/routes.xml & menu.xml
│   ├── db_schema.xml             # Deklaratywny schemat bazy z odpowiednimi indexami (złożonymi)
│   ├── di.xml                    # Bindowanie interfejsów, konfiguracja argumentów, pluginy
│   └── schema.graphqls           # Typy i mutacje dla Headless
└── view/
    ├── adminhtml/
    │   └── ui_component/         # Gridy (Listing) i formularze w panelu admina
    └── frontend/
        ├── layout/               # Definicje układu stron
        ├── templates/            # Szablony .phtml (pisane z myślą o ViewModelach)
        └── web/                  # Zasoby: Alpine.js/Tailwind (Hyvä) lub RequireJS/Knockout (Luma)
```

## 3. KLUCZOWE KOMPONENTY I LOGIKA (Core Logic & Snippets)

### A. Frontend: ViewModels zamiast Bloków (Best Practice)
Nowoczesny frontend M2 unika przeładowanych klas `Block`. Zamiast tego wstrzykuje się lekki `ViewModel` z logiką prezentacyjną bezpośrednio do szablonu.
*Boilerplate:*
```xml
<!-- w layout.xml -->
<block name="custom.block" template="Vendor_Module::template.phtml">
    <arguments>
        <argument name="view_model" xsi:type="object">Vendor\Module\ViewModel\CustomData</argument>
    </arguments>
</block>
```
```php
// W szablonie PHTML
/** @var \Vendor\Module\ViewModel\CustomData $viewModel */
$viewModel = $block->getViewModel();
$items = $viewModel->getItems();
```

### B. Service Layer (Brak logiki biznesowej w kontrolerach)
Kontrolery nie mogą zawierać zapytań SQL ani złożonej logiki. Ich jedynym celem jest odebranie requestu, przekazanie go do `ManagementInterface` (lub innej usługi) i zwrócenie wyniku (Redirect/JSON/Page).
*Boilerplate Kontrolera:*
```php
public function execute() {
    $entityId = (int) $this->getRequest()->getParam('id');
    try {
        $this->managementService->processAction($entityId);
        $this->messageManager->addSuccessMessage(__('Action successful.'));
    } catch (\Exception $e) {
        $this->logger->error('Error processing ID ' . $entityId . ': ' . $e->getMessage());
        $this->messageManager->addErrorMessage(__('An error occurred.'));
    }
    return $this->resultRedirectFactory->create()->setPath('*/*/index');
}
```

### C. Zabezpieczanie Danych (IDOR i Injections)
Na frontendzie i w GraphQL zawsze nakładaj filtr bezpieczeństwa po zaaplikowaniu parametrów paginacji i sortowania, aby użytkownik nie nadpisał filtru.
```php
public function getListForCustomer(int $customerId, SearchCriteriaInterface $searchCriteria): SearchResultsInterface {
    $collection = $this->collectionFactory->create();
    $this->collectionProcessor->process($searchCriteria, $collection);
    // Filtr bezpieczeństwa musi być wymuszony po wywołaniu process()
    $collection->addFieldToFilter('customer_id', ['eq' => $customerId]);
    // ...
}
```

## 4. DOBRE PRAKTYKI I OPTYMALIZACJA (Best Practices & Performance)

1. **Hyvä Themes vs Luma:**
   Jeśli moduł posiada frontend, używaj `Alpine.js` i `TailwindCSS` (dla Hyvä) zamiast `RequireJS`, `jQuery` i `Knockout.js` (Luma), chyba że projekt jest oparty stricte na Lumie. Escape'uj dane w szablonach ( `$escaper->escapeHtml()` / `escapeUrl()` ).
2. **Optymalizacja Wydajności (Unikanie N+1):**
   W `ViewModels` i `Blocks` (lub GraphQL Resolvers) cache'uj pobrane kolekcje wewnątrz zmiennej instancji, by kolejne wywołanie metody z szablonu nie obciążało bazy (Lazy-loading przez `if ($this->items !== null) return $this->items;`).
3. **Niezawodne Mass Actions w Adminie:**
   Nigdy nie przerywaj głównej pętli, gdy jeden z elementów rzuci wyjątek. Otaczaj operacje na pojedynczym elemencie blokiem `try/catch (\Exception $e)` i loguj to przez wstrzyknięty `Psr\Log\LoggerInterface` wymieniając konkretne ID, które sprawiło problem.
4. **Zoptymalizowane zapytania i Baza Danych:**
   Twórz indeksy złożone w `db_schema.xml` dla zapytań (np. Cron), które filtrują równocześnie po kilku kolumnach (np. `status` i `updated_at`). Nie wykonuj pętli z wywołaniem `$repository->save()` setki razy — w razie potrzeby importu hurtowego używaj natywnych operacji bulk lub insertOnDuplicate.
5. **Config Model i Puste Multiselecty:**
   W modelach Config, pobierając wartości wielokrotnego wyboru (multiselect), pamiętaj, że gdy admin odznaczy wszystko, Magento zwróci puaty string (`''`), a gdy pole jest w ogóle niezdefiniowane, zwróci `null`. Sprawdzaj parametry przez `if ($value === null)` aby prawidłowo interpretować brak wyboru.

---

## 5. PROMPT GENERATYWNY (Execution Prompt)

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

2. **Bezpieczeństwo (IDOR, Mass Assignment, Pliki):**
   Jeśli zapytanie dotyczy zasobów konkretnego klienta (Frontend / GraphQL), nakładaj filtr `$collection->addFieldToFilter('customer_id', $id)` zawsze **PO** wywołaniu `collectionProcessor->process(...)`, by zapobiec atakom polegającym na nadpisywaniu filtrów w SearchCriteria. Wgrywane pliki weryfikuj zawsze funkcją serwerową `finfo` (MIME-type), nigdy nie ufaj rozszerzeniom i superglobalnej `$_FILES`. Używaj `$escaper` w szablonach HTML.

3. **Wydajność bazy i ochrona przed zjawiskiem N+1:**
   Wszelkie metody ładujące dane do widoku (np. zagnieżdżone elementy w ViewModelu lub GraphQL Resolverze) muszą cachować wyniki wewnątrz instancji klasy przy użyciu lazy-loadingu (np. `if ($this->cachedData !== null) return $this->cachedData;`). Skrypty cykliczne i skomplikowane kolekcje filtrujące po wielu kolumnach wymagają stworzenia dla nich indeksów złożonych w `db_schema.xml`.

4. **Odseparowanie akcji pobocznych (Events / Observers):**
   Akcje takie jak wysyłka e-mail, logowanie audytowe lub asynchroniczna komunikacja zewnętrzna nie mogą obciążać logiki rdzennej (np. po zatwierdzeniu zmian przez admina). Dysponuj eventem, nasłuchuj za pomocą `ObserverInterface`. Do emaili używaj `TransportBuilder` i pauzuj lokalizację (`inlineTranslation->suspend()`).

5. **Niezawodność i logowanie wyjątków w Adminie / Mass Actions:**
   Dodawaj precyzyjne zasoby ACL w `acl.xml` (nie grupuj wszystkiego pod głównym resource, daj np. uprawnienie tylko do akceptacji, usunięcia, wglądu). W Mass Action'ach kontrolerów (np. MassDelete, MassApprove) ZAWSZE wyłapuj `\Exception` w pojedynczym obrocie pętli i loguj jego treść z ID wykraczającej encji poprzez wstrzyknięte `Psr\Log\LoggerInterface`. Nie pozwól, aby błąd na jednym elemencie zatrzymał wykonanie pozostałych operacji.

6. **Konfiguracja Store i metody statyczne:**
   Nie twórz metod statycznych w celu obejścia Dependency Injection (np. do tłumaczenia statusów). Używaj dedykowanego modelu `Config.php` do zaczytywania wszelkich zmiennych konfiguracyjnych ze ScopeConfig. Zwracaj szczególną uwagę na rozróżnianie pustego wyboru multiselectu (`$value === ''`) od całkowitego braku ustawienia z fallbackiem do wartości domyślnej (`$value === null`).

Proszę, rozpocznij pracę od przygotowania kompletnej struktury katalogów dopasowanej do mojego stacku oraz omówienia w punktach jak zrealizujesz moje wymagania według tych zasad. Następnie poczekaj na moją akceptację przed wygenerowaniem faktycznego kodu.
```
