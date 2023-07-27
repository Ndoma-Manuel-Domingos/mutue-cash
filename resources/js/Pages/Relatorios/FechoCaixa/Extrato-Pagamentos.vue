<template>
    <MainLayouts>
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0">Extratos de Pagamentos</h1>
            </div>
            <div class="col-sm-6"></div>
          </div>
        </div>
      </div>
  
      <div class="content">
        <div class="container-fluid">
          <div class="row">
            <div class="col-12 col-md-12">
              <div class="card">
                <form action="">
                  <div class="card-body">
                    <div class="row">
    
                      <div class="col-12 col-md-2">
                        <div class="form-group">
                          <label for="">Data Inicio</label>
                          <input
                            type="date"
                            placeholder="informe do Inicio"
                            class="form-control"
                            v-model="data_inicio"
                          />
                        </div>
                      </div>
  
                      <div class="col-12 col-md-2">
                        <div class="form-group">
                          <label for="">Data Final</label>
                          <input
                            type="date"
                            placeholder="informe do final"
                            class="form-control"
                            v-model="data_final"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-12 col-md-12">
              <div class="card">
                <div class="card-header">
                
                  <button
                    class="btn btn-success float-right mr-1"
                    type="button"
                    @click="imprimirEXCEL"
                  >
                    <i class="fas fa-file-excel"></i>
                    EXCEL
                  </button>
  
                  <button
                    class="btn btn-danger float-right mr-1"
                    type="button"
                    @click="imprimirPDF"
                  >
                    <i class="fas fa-file-pdf"></i>
                    PDF
                  </button>
                </div>
  
                <div class="table-responsive">
                  <table class="table table-hover text-nowrap">
                    <thead>
                      <tr>
                        <th>Nº</th>
                        <th>Matricula</th>
                        <th>Serviço Pago</th>
                        <th>Nome</th>
                        <th>Curso</th>
                        <th>Data</th>
                        <th class="text-right">Total Pago</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in items.data" :key="item.Codigo">
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                        <td class="text-right">0</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
  
                <div class="card-footer">
                  <Link href="" class="text-secondary">
                    TOTAL REGISTROS: {{ items.total }}
                  </Link>
                  <Paginacao
                    :links="items.links"
                    :prev="items.prev_page_url"
                    :next="items.next_page_url"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </MainLayouts>
  </template>
    
<script>
  import Paginacao from "../../../Shared/Paginacao";
  import { Link } from "@inertiajs/inertia-vue3";
  
  export default {
    props: ["items"],
    components: { Link, Paginacao },
    data() {
      return {
        data_inicio: "",
        data_final: "",
  
        params: {},
      };
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
      data_inicio: function (val) {
        this.params.data_inicio = val;
        this.updateData();
      },
      data_final: function (val) {
        this.params.data_final = val;
        this.updateData();
      },
    },
    methods: {
      updateData() {
        this.$Progress.start();
        this.$inertia.get("/relatorios/extrato-pagamentos", this.params, {
          preserveState: true,
          preverseScroll: true,
          onSuccess: () => {
            this.$Progress.finish();
          },
        });
      },
  
      formatValor(atual) {
        const valorFormatado = Intl.NumberFormat("pt-br", {
          style: "currency",
          currency: "AOA",
        }).format(atual);
        return valorFormatado;
      },
  
      imprimirPDF() {
        window.open(
          `/relatorios/fecho-caixa/operador/pdf`,
          "_blank"
        );
      },
  
      imprimirEXCEL() {
        window.open(
          `/relatorios/fecho-caixa/operador/excel`,
          "_blank"
        );
      },
    },
  };
  </script>
      
      