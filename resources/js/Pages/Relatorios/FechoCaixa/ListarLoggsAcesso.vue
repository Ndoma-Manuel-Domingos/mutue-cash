<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h4 class="m-0 text-uppercase">Listar Loggs de Acesso</h4>
          </div>
          <!-- <div class="col-sm-6">
            <a
              @click="imprimirPDF"
              class="btn btn-danger btn-sm float-sm-right mr-2"
              ><i class="fas fa-file-pdf"></i> PDF</a
            >
            <a
              @click="imprimirEXCEL"
              class="btn btn-success btn-sm float-sm-right mr-2"
              ><i class="fas fa-file-excel"></i> Excel</a
            >
          </div> -->
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <form action="">
              <div class="card card-light">
                <div class="card-body">
                  <div class="row">
                    <div class="col-12 col-md-3">
                      <div class="form-group">
                        <label for="" class="text-secondary">Operador</label>
                        <div class="input-group input-group">
                          <Select2
                            v-model="operador_id"
                            id="operador_id"
                            class="col-12 col-md-12"
                            :options="utilizadores"
                            :settings="{ width: '100%' }"
                          />
                        </div>
                      </div>
                    </div>

                    <div class="col-12 col-md-3">
                      <div class="form-group">
                        <label for="" class="text-secondary">Data Inicio</label>
                        <div class="input-group input-group">
                          <input
                            v-model="data_inicio"
                            type="date"
                            id="data_inicio"
                            class="col-12 col-md-12 form-control"
                          />
                        </div>
                      </div>
                    </div>

                    <div class="col-12 col-md-2">
                      <div class="form-group">
                        <label for="" class="text-secondary">Data Final</label>
                        <div class="input-group input-group">
                          <input
                            v-model="data_final"
                            type="date"
                            id="data_final"
                            class="col-12 col-md-12 form-control"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header bg-light">
                <h5>
                  <span class="float-left"
                    >TOTAL DE REGISTOS: {{ items.total }}</span
                  >
                </h5>
              </div>

              <div class="card-body">
                <div class="table-responsive">
                  <table
                    id="carregarTabelaEstudantes"
                    style="width: 100%"
                    class="table-sm table_estudantes table-bordered table-striped"
                  >
                    <thead>
                      <tr>
                        <th>Codigo</th>
                        <th>Descrição</th>
                        <th>IP</th>
                        <th>Browser</th>
                        <th>Rota Acessada</th>
                        <th>Operador</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in items.data" :key="item.id">
                        <td>{{ item.id }}</td>
                        <td>{{ item.descricao }}</td>
                        <td>{{ item.ip_maquina }}</td>
                        <td>{{ item.browser }}</td>
                        <td>{{ item.rota_acessado }}</td>
                        <td>{{ item.operador.nome ?? "" }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="card-footer">
                <Link href="" class="text-secondary">
                  Total DE REGISTOS: {{ items.total }}
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
import { Link } from "@inertiajs/inertia-vue3";
import Paginacao from "../../../Shared/Paginacao.vue";

export default {
  props: ["items", "utilizadores"],
  components: {
    Link,
    Paginacao,
  },
  data() {
    return {
      params: {},
      
      data_inicio: "",
      data_final: "",
      operador_id: "",
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
    
    operador_id: function (val) {
      this.params.operador_id = val;
      this.updateData();
    },

  },
  methods: {
    updateData() {
      this.$Progress.start();
      this.$inertia.get("/relatorios/listar-loggs-acesso", this.params, {
        preserveState: true,
        preverseScroll: true,
        onSuccess: () => {
          this.$Progress.finish();
        },
      });
    },

  },
};
</script>
