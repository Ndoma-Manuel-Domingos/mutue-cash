<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-info">DETALHE DE MOVIMENTOS</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <a href="/movimento-fluxo">Listagem</a>
              </li>
              <li class="breadcrumb-item active">Detalhe</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12 col-md-12">
            <div class="card">
              <div class="card-header">
                <div class="row">
                    <div class="col-12 col-md-2">
                        <span class="lead"><strong>Nº Lancamento:</strong> {{ movimento.lancamento_atual }}</span>
                    </div>
                    <!-- <div class="col-12 col-md-2">
                        <span class="lead"><strong>Diário:</strong> {{ movimento.diario.designacao }}</span>
                    </div> -->
                    <!-- <div class="col-12 col-md-2">
                        <span class="lead"><strong>Tipo Documento:</strong> {{ movimento.tipo_documento.designacao }}</span>
                    </div> -->
                    <div class="col-12 col-md-2">
                        <span class="lead"><strong>Exercício:</strong> {{ movimento.exercicio.designacao }}</span>
                    </div>
                    <div class="col-12 col-md-2">
                        <span class="lead"><strong>Data:</strong> {{ movimento.data_lancamento }}</span>
                    </div>
                    <!-- <div class="col-12 col-md-2">
                        <span class="lead"><strong>Operador:</strong> {{ movimento.criador.name }}</span>
                    </div> -->
                </div>
              </div>
              <div class="card-body table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Descrição</th>
                      <th>Conta</th>
                      <th>Subconta</th>
                      <th class="text-right">Débito</th>
                      <th class="text-right">Crédito</th>
                      <th class="text-right">IVA</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in movimento.items" :key="index">
                      <td>{{ index + 1 }}</td>
                      <td> {{ item.descricao }} </td>
                      <td>
                        {{ item.subconta.conta.numero }} -
                        {{ item.subconta.conta.designacao }}
                      </td>
                      <td>
                        {{ item.subconta.numero }} -
                        {{ item.subconta.designacao }}
                      </td>
                      <td class="text-primary text-right">{{ formatarValorMonetario(item.debito) }}</td>
                      <td class="text-danger text-right">{{ formatarValorMonetario(item.credito) }}</td>
                      <td class="text-right">{{ formatarValorMonetario(item.iva) }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td></td>
                      <td></td>
                      <td></td>
                      <th class="text-primary text-right">{{ formatarValorMonetario(movimento.debito) }}</th>
                      <th class="text-danger text-right">{{ formatarValorMonetario(movimento.credito) }}</th>
                      <th class="text-right">{{ formatarValorMonetario(movimento.iva) }}</th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-12">
            <div class="card">
              <div class="card-body">
                <div class="row">
                  <div class="col-6">
                    <p class="lead">Descrição</p>
                    
                    <p>{{ movimento.descricao }}</p>
                  </div>

                  <div class="col-6">
                    <p class="lead">Resumo</p>
                    <div class="table-responsive">
                      <table class="table">
                        <tr>
                          <th style="width: 50%">Total Débito:</th>
                          <td>{{ formatarValorMonetario(movimento.debito) }}</td>
                        </tr>
                        <tr>
                          <th>Total Crédito:</th>
                          <td>{{ formatarValorMonetario(movimento.credito) }}</td>
                        </tr>
                        <tr>
                          <th>Total IVA:</th>
                          <td>{{ formatarValorMonetario(movimento.iva) }}</td>
                        </tr>
                      </table>
                    </div>
                  </div>
                </div>
                
                <div class="row">
                    <div class="col-12 col-md-12">
                        <button type="button" class="btn btn-info float-left"><i class="fas fa-print"></i> Imprimir </button>
                    </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayouts>
</template>
  
<script>

export default {
  props: ["movimento"],
  data() {
    return {};
  },
  mounted() {},
  methods: {
    formatValor(atual) {
      const valorFormatado = Intl.NumberFormat("pt-br", {
        style: "currency",
        currency: "AOA",
      }).format(atual);
      return valorFormatado;
    },
    
    formatarValorMonetario(valor) {
      // Converter o número para uma string e separar parte inteira da parte decimal
      let partes = String(valor).split(".");
      let parteInteira = partes[0];
      let parteDecimal = partes.length > 1 ? "." + partes[1] : "";

      // Adicionar separadores de milhar
      parteInteira = parteInteira.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

      // Retornar o valor formatado
      return parteInteira + parteDecimal;
    },
    
  },
};
</script>
  
  