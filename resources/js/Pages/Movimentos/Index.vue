<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-info">LISTAGEM DE MOVIMENTOS</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="/movimento-fluxo">Dashboard</a></li>
              <li class="breadcrumb-item active">Listagem</li>
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
                <a @click="imprimirPlano()" class="btn btn-sm mx-1 btn-danger float-right"> <i class="fas fa-file-pdf"></i> Visualizar</a>
                <a href="" class="btn btn-sm mx-1 btn-success float-right"> <i class="fas fa-file-excel"></i> Exportar</a>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-hover" id="tabela_de_movimentos">
                  <thead>
                    <tr>
                      <th style="cursor: pointer;">Nº</th>
                      <th style="cursor: pointer;">Descrição</th>
                      <th>Débito</th>
                      <th>Crédito</th>
                      <th>Data</th>
                      <th>Exercício</th>
                      <th class="text-right">Ações</th>
                    </tr>
                  </thead>
                  
                  <tbody>
                    
                    <tr v-for="item in movimentos" :key="item">
                      <td>{{ item.id }}</td>
                      <td>{{ item.descricao }}</td>
                      <td class="text-info"><strong>{{ item.debito == 0 ? '-' : formatarValorMonetario(item.debito) }}</strong></td>
                      <td class="text-danger"><strong>{{ item.credito == 0 ? '-' : formatarValorMonetario(item.credito) }}</strong></td>
                      <td>{{ item.data_lancamento }}</td>
                      <td>{{ item.exercicio.designacao }}</td>
                       <td>
                        <div class="float-right">
                          <!-- <a :href="`/movimento-fluxo/${item.id}/edit`" class="btn btn-sm btn-success mx-1"><i class="fas fa-edit"></i> Editar</a> -->
                          <a :href="`/movimento-fluxo/${item.id}`" class="btn btn-sm btn-info mx-1"><i class="fas fa-info-circle"></i> Detalhe</a>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
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

  props: [
    'movimentos'
  ],

  data() {
    return {
      params: {},
    };
  },
  mounted() {
    $('#tabela_de_movimentos').DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": true,
    });
  },    
  watch: {
    options: function (val) {
      this.params.page = val.page;
      this.params.page_size = val.itemsPerPage;
      if (val.sortBy.length != 0) {
        this.params.sort_by = val.sortBy[0];
        this.params.order_by = val.sortDesc[0] ? "desc" : "asc";
      } else {
        this.params.sort_by = null;
        this.params.order_by = null;
      }
      this.updateData();
    },
  },
  
  methods: {
    updateData() {
      this.$Progress.start();
      this.$inertia.get("/movimentos", this.params, {
        preserveState: true,
        preverseScroll: true,
        onSuccess: () => {
          this.$Progress.finish();
        },
      });
    },
    imprimirPlano() {
      window.open("imprimir-movimentos");
    },
    
    formatarValorMonetario(valor) {
        // Converter o número para uma string e separar parte inteira da parte decimal
        let partes = String(valor).split('.');
        let parteInteira = partes[0];
        let parteDecimal = partes.length > 1 ? '.' + partes[1] : '';
    
        // Adicionar separadores de milhar
        parteInteira = parteInteira.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    
        // Retornar o valor formatado
        return parteInteira + parteDecimal;
    },

  },
};
</script>
  
  